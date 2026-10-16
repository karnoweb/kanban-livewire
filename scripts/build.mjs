import fs from 'node:fs';
import path from 'node:path';
import { compile } from '@tailwindcss/node';

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
const compiler = await compile(fs.readFileSync('resources/css/standalone.css', 'utf8'), {
  base: path.resolve('resources/css'), onDependency() {},
});
const candidates = fs.readdirSync('resources/views', { recursive: true })
  .filter(file => file.endsWith('.blade.php'))
  .flatMap(file => fs.readFileSync(path.join('resources/views', file), 'utf8').match(/[a-zA-Z0-9_:-]+/g) || []);
fs.writeFileSync('resources/dist/kanban.css', compiler.build([...new Set(candidates)]));
const bundle = order.map((file) => `/* ${file} */\n${fs.readFileSync(`resources/js/${file}`, 'utf8')}`).join('\n');
fs.writeFileSync('resources/dist/kanban.js', bundle);
console.log(`kanban.js ${bundle.length} bytes`);
