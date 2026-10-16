import fs from 'node:fs';
import path from 'node:path';

// Acceptance evidence is read from actual test reports, never inferred from source presence.
const suites = {
  m: 'EnterpriseModelsTest', a: 'EnterpriseActionsAndViewsTest', x: 'EnterpriseAcceptanceTest',
  p: 'EnterpriseQueryPerformanceTest', c: 'EnterpriseConcurrencyTest', k: 'AccessibilityTest',
  t: 'AllThemesAndDirectionsTest', r: 'RealtimeMultiBoardTest',
};
const test = (suite, name) => `${suites[suite]}::test_${name}`;
const rows = [
  ['اتصال Trait و تعریف مرکزی', [test('m', 'trait_model_can_be_displayed_through_the_existing_factory')], '۱ Task؛ ۳ ستون؛ نمایش tasks:1'],
  ['رد مدل بدون Trait', [test('m', 'registry_rejects_models_without_trait_and_duplicate_sources')], 'مدل نامعتبر و ثبت تکراری هر دو رد شوند'],
  ['ثبت مدل بدون دسترسی برد', [test('m', 'unknown_and_raw_card_ids_are_indistinguishable_from_missing'), test('m', 'other_registered_sources_are_never_queried')], 'tickets:1 در برد tasks داده ندهد؛ صفر query منبع دیگر'],
  ['برد Task بدون کوئری کلاس', [test('m', 'other_registered_sources_are_never_queried')], 'صفر query به enterprise_classes'],
  ['برد کلاس بدون کوئری Task', [test('x', 'class_only_board_never_queries_tasks_or_tickets')], '۱ کلاس؛ صفر query به tasks/tickets'],
  ['شناسه مشترک دو منبع', [test('m', 'mixed_sources_have_namespaced_ids_and_explicit_mapped_stages')], 'tasks:1 و tickets:1 مستقل؛ شمارش ۲'],
  ['تولید ستون از Enum', [test('x', 'cast_backed_enum_generates_stages_in_declared_order')], 'ترتیب todo، doing، done'],
  ['نگاشت مراحل چند مدل', [test('m', 'mixed_sources_have_namespaced_ids_and_explicit_mapped_stages')], 'todo و open در ستون مشترک؛ شمارش ۲'],
  ['رد نگاشت ناقص', [test('m', 'mixed_board_requires_complete_explicit_stage_mapping')], 'خطای تعریف برد؛ داده مخلوط نشود'],
  ['جداسازی چند مؤلفه و کاربر', [test('x', 'two_instances_keep_actor_scoped_card_state_separate')], 'دو instanceId متفاوت؛ هر کاربر فقط کارت خود'],
  ['صد هزار کارت', [test('p', 'ten_columns_at_ten_and_one_hundred_thousand_rows_have_bounded_hydration')], '100000 ردیف؛ ۱۰ ستون؛ ۲۵۰ کارت؛ ۲۶۰ hydration؛ ۲۱ query'],
  ['صفحه‌بندی مستقل ستون', [test('x', 'each_column_has_an_independent_cursor_and_constant_count')], 'دو ستون با شمارش ۳؛ نشانگر مستقل و بدون تکرار'],
  ['رد نشانگر برد دیگر', [test('m', 'cursor_is_bound_to_every_security_and_query_dimension')], 'نشانگر tasks روی other رد شود'],
  ['رد نشانگر محدوده یا کاربر دیگر', [test('m', 'cursor_is_bound_to_every_security_and_query_dimension')], 'تغییر scope یا actor نشانگر را نامعتبر کند'],
  ['ابطال نشانگر با جست‌وجو یا فیلتر', [test('m', 'cursor_is_bound_to_every_security_and_query_dimension')], 'تغییر search/جهت/ستون و دستکاری نشانگر رد شود'],
  ['ترتیب پایدار و بدون تکرار', [test('m', 'keyset_pages_have_no_duplicates_with_tied_ranks'), test('x', 'mixed_source_keyset_pagination_returns_every_id_once')], '۱۷ کارت هم‌رتبه؛ ۲۲ کارت دو منبع؛ بدون گم‌شدن/تکرار'],
  ['جست‌وجوی فارسی و انگلیسی', [test('m', 'search_and_filters_are_applied_before_counts_and_pages')], 'قرارداد و other؛ شمارش فیلترشده ۱'],
  ['فیلتر پیش از صفحه‌بندی', [test('m', 'search_and_filters_are_applied_before_counts_and_pages')], 'priority/status/assignee در SQL؛ کاربر غیرمجاز صفر'],
  ['محدوده امن تجمیع', [test('m', 'read_scope_applies_to_pages_counts_details_and_metrics')], '۱۲ مبلغ مجاز؛ ۹۰۰ مبلغ کاربر دیگر نادیده'],
  ['جلوگیری از کوئری به‌ازای هر کارت', [test('x', 'eager_loaded_assignees_have_a_constant_query_count_for_twenty_five_cards')], '۲۵ کارت با صاحب مشترک؛ فقط ۳ query'],
  ['بسته‌بودن دسترسی خالی یا خاموش', [test('m', 'empty_allowlist_returns_zero_data'), test('a', 'model_adapter_can_be_disabled_fail_closed')], 'صفر داده و صفر query منابع؛ نوشتن ممنوع'],
  ['مجوز یکسان لیست و بایگانی', [test('m', 'archive_and_restore_share_the_same_visibility_scope'), test('m', 'read_scope_applies_to_pages_counts_details_and_metrics')], 'بایگانی کارت مجاز ۱؛ کارت کاربر دیگر قابل خواندن نباشد'],
  ['انتقال و افزایش نسخه', [test('m', 'valid_move_persists_version_and_operation_status')], 'todo به doing؛ نسخه ۱ به ۲'],
  ['رد انتقال جعلی', [test('m', 'domain_transition_matrix_cannot_be_bypassed')], 'todo به done ممنوع؛ وضعیت اولیه حفظ شود'],
  ['رد نوشتن روی منبع خارج برد', [test('m', 'foreign_source_and_foreign_owner_writes_are_not_found')], 'tickets:1 در برد tasks برابر NOT_FOUND'],
  ['انتخاب خودکار منبع ایجاد', [test('m', 'create_resolves_single_source_and_domain_hook_assigns_owner')], '۱ منبع؛ owner=alice از سرور'],
  ['انتخاب صریح منبع ایجاد ترکیبی', [test('m', 'multi_source_create_requires_source'), test('x', 'multisource_create_accepts_only_the_explicit_selected_source')], 'فقدان source رد؛ انتخاب tasks مجاز'],
  ['ویرایش کارت کاربر دیگر', [test('m', 'foreign_source_and_foreign_owner_writes_are_not_found')], 'کارت خارجی و ناموجود هر دو NOT_FOUND'],
  ['بایگانی و بازیابی امن', [test('m', 'archive_and_restore_share_the_same_visibility_scope'), test('a', 'disabled_feature_is_enforced_by_direct_handler_calls')], 'مجوز برد و نسخه بررسی؛ قابلیت خاموش تغییری ندهد'],
  ['عملیات اختصاصی و فرم دامنه', [test('a', 'custom_actions_use_declared_callbacks_and_structured_form_data'), test('a', 'custom_action_revalidates_its_domain_form')], 'approve معتبر ذخیره؛ note خالی رد؛ متد خام اجرا نشود'],
  ['تأیید، یادداشت و لغو', [test('a', 'confirmation_note_is_required_again_by_the_handler'), test('x', 'cancelling_a_confirmation_has_no_database_side_effect')], 'یادداشت اجباری؛ لغو صفر تغییر'],
  ['اجرای تکراری بدون اثر دوم', [test('m', 'duplicate_operations_do_not_write_twice')], 'دو درخواست؛ فقط افزایش نسخه از ۱ به ۲'],
  ['تعارض نسخه کهنه', [test('m', 'stale_and_missing_versions_cannot_overwrite')], 'نسخه ۵؛ expected=1 یا null رد؛ عنوان اولیه حفظ'],
  ['رقابت آخرین ظرفیت', [test('c', 'two_real_processes_compete_for_one_remaining_capacity_slot')], '۲ پردازش واقعی؛ دقیقاً ۱ success و ۱ wip_limit'],
  ['مرتب‌سازی میان ده هزار کارت', [test('x', 'manual_move_with_ten_thousand_neighbors_writes_one_row')], '10000 همسایه؛ فقط ۱ UPDATE'],
  ['برخورد رتبه و بازتنظیم محدود', [test('a', 'dense_rank_collision_rebalances_only_local_neighbors'), test('a', 'equal_rank_anchors_rebalance_without_reversing_the_requested_neighbors')], 'ترتیب نهایی ۱،۳،۲؛ رتبه‌های یکتا'],
  ['رد ترتیب ترکیبی بدون ذخیره مشترک', [test('m', 'mixed_manual_order_requires_shared_adapter')], 'UNSUPPORTED با rank_storage_required'],
  ['عملیات گروهی جزئی و اتمی', [test('a', 'partial_bulk_reports_unauthorized_items_without_writing_them'), test('a', 'atomic_bulk_rolls_back_all_items_on_one_failure'), test('a', 'atomic_bulk_commits_all_valid_items')], 'جزئی ۱ موفق/۲ خطا؛ اتمی خطادار صفر تغییر؛ اتمی معتبر ۲ موفق'],
  ['برد، لیست و ویجت', [test('a', 'list_and_compact_use_the_same_provider')], 'سه نما از یک Provider؛ شمارش ۳ پس از فیلتر مرورگر', ['KNB-139']],
  ['گروه‌بندی با محدوده مجاز', [test('a', 'swimlane_groups_and_counts_use_visibility_queries')], 'گروه A/B؛ گروه Private دیده نشود؛ گروه A شمارش ۱'],
  ['مجوز تغییر گروه', [test('x', 'cross_swimlane_move_is_denied_without_explicit_domain_guard')], 'بدون guard رد؛ team اولیه A حفظ شود'],
  ['آمار مستقل از کارت‌های صفحه', [test('a', 'metrics_use_all_matching_rows_instead_of_only_loaded_cards')], '۲۶ رکورد؛ صفحه ۲۵؛ مجموع صحیح ۲۶'],
  ['بستن ستون و حفظ ترجیح', [test('a', 'collapse_preference_survives_remount')], 'todo بسته؛ پس از بارگذاری دوباره بسته بماند', ['KNB-143']],
  ['لمس و صفحه‌کلید', [test('a', 'keyboard_move_includes_the_loaded_version'), test('k', 'keyboard')], 'انتقال با نسخه معتبر؛ لمس واقعی در مرورگر شبیه‌سازی دستگاه', ['KNB-143', 'KNB-144']],
  ['راست‌به‌چپ، پوسته و موبایل', [test('t', 'themes')], 'جهت و پوسته‌های موجود؛ عرض موبایل ۳۹۰', ['KNB-144']],
  ['جداسازی شنونده‌های چند برد', [test('r', 'isolation')], '۲ شنونده؛ حذف یک برد ۱؛ حذف هر دو صفر', ['KB-038/KB-044', 'KB-043 listeners']],
  ['عدم افشای منبع در Broadcast', [test('x', 'model_broadcast_rejects_a_foreign_source_and_redacts_row_identifiers')], 'منبع خارجی صفر رویداد؛ رویداد مجاز بدون entityId/version'],
  ['رویداد فقط پس از ثبت', [test('a', 'after_commit_event_is_dispatched_once_at_transaction_level_zero'), test('a', 'rollback_emits_no_domain_event')], 'فقط ۱ رویداد در سطح تراکنش صفر؛ rollback بدون رویداد'],
  ['قرارداد همزیستی تقویم', [test('x', 'calendar_and_kanban_traits_share_one_domain_record_without_package_dependency')], 'یک رکورد و دو Trait؛ بدون وابستگی اجباری. پکیج تقویم خارجی نصب/آزمایش نشده است'],
  ['سازگاری و مجموعه کامل', ['@all-php'], 'تمام تست‌های قبلی و جدید، صفر خطا/رد/skip و مجموعه کامل مرورگر', ['@all-browser']],
];

const xmlPath = process.argv[2] || '.phpunit.cache/enterprise-junit.xml';
const browserPath = process.argv[3] || '.phpunit.cache/enterprise-browser.json';
const xml = fs.readFileSync(xmlPath, 'utf8');
const browser = JSON.parse(fs.readFileSync(browserPath, 'utf8'));
const attributes = text => Object.fromEntries([...text.matchAll(/([\w-]+)="([^"]*)"/g)].map(match => [match[1], match[2]]));
const cases = new Map();
for (const match of xml.matchAll(/<testcase\b([^>]*?)(?:\/>|>([\s\S]*?)<\/testcase>)/g)) {
  const attr = attributes(match[1]);
  const className = (attr.class || '').split('\\').pop();
  cases.set(`${className}::${attr.name}`, { file: attr.file, assertions: Number(attr.assertions),
    pass: !/<(?:failure|error|skipped)\b/.test(match[2] || '') });
}
const summary = attributes(xml.match(/<testsuite\b([^>]*)>/)[1]);
const phpPass = Number(summary.tests) >= 146 && ['failures', 'errors', 'skipped'].every(key => Number(summary[key]) === 0);
const specs = [];
function flatten(suite) { for (const spec of suite.specs || []) specs.push(spec); for (const child of suite.suites || []) flatten(child); }
for (const suite of browser.suites || []) flatten(suite);
const browserPass = specs.length >= 11 && specs.every(spec => spec.ok);
const measuredAt = new Date().toISOString();
const results = rows.map(([title, tests, expected, browserTests = []], index) => {
  const php = tests.every(name => name === '@all-php' ? phpPass : cases.get(name)?.pass === true);
  const ui = browserTests.every(name => name === '@all-browser' ? browserPass : specs.some(spec => spec.title.includes(name) && spec.ok));
  const id = `KNB${101 + index}`;
  const result = { id, title, tests, browserTests, expected, status: php && ui ? 'PASS' : 'NOT_VERIFIED', measuredAt };
  const links = tests.map(name => {
    const entry = cases.get(name);
    return entry ? `- ${name} — ${entry.assertions} بررسی — ${path.relative(process.cwd(), entry.file).replaceAll('\\', '/')}` : `- ${name}`;
  }).join('\n');
  fs.writeFileSync(`tests/Scenarios/${id}.md`, `# ${id} — ${title}\n\n## آماده‌سازی\n\nپایگاه داده SQLite جدا برای هر تست؛ هویت و محدوده معتبر سرور. داده‌ها و تعداد دقیق در متدهای PHP زیر ساخته می‌شوند. آزمون مرورگر از پایگاه داده آزمایشی جدا استفاده می‌کند.\n\n## عملیات و مقادیر\n\n${expected}\n\n## آزمون خودکار PHP\n\n${links}\n\n## مرورگر\n\n${browserTests.length ? browserTests.join('، ') : 'برای این قرارداد، آزمون پایگاه داده/سرور معیار است.'}\n\n## نتیجه واقعی\n\n${result.status} — ${measuredAt}\n\nمبنا: گزارش ${xmlPath} و ${browserPath}؛ وجود فایل آزمون به‌تنهایی PASS محسوب نمی‌شود.\n`);
  return result;
});
fs.writeFileSync('docs/ENTERPRISE-SCENARIO-RESULTS.json', JSON.stringify({ measuredAt, php: summary, browser: browser.stats, scenarios: results }, null, 2));
console.log(`${results.filter(row => row.status === 'PASS').length}/50 scenarios verified from executed reports`);
if (results.some(row => row.status !== 'PASS')) process.exitCode = 1;
