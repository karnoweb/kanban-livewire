import fs from 'node:fs';

const order = [
  'connection-state.js',
  'announcer.js',
  'focus-manager.js',
  'confirmation.js',
  'sort-adapter.js',
  'board-interactions.js',
  'virtualizer.js',
  'realtime-bridge.js',
  'kanban.js',
];

fs.mkdirSync('resources/dist', { recursive: true });
fs.copyFileSync('resources/css/kanban.css', 'resources/dist/kanban.css');
const bundle = order.map((file) => `/* ${file} */\n${fs.readFileSync(`resources/js/${file}`, 'utf8')}`).join('\n');
fs.writeFileSync('resources/dist/kanban.js', bundle);
console.log(`kanban.js ${bundle.length} bytes`);
