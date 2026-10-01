// Kullanım: node tools/demo-images/generate.mjs  → database/demo/images/*.jpg
// Playwright (Chromium) gerektirir; yalnız geliştirme ortamında çalıştırılır.
import { createRequire } from 'module';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import * as S from './scenes.mjs';

const here = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(here, '../..');
const out = path.join(root, 'database/demo/images');
fs.mkdirSync(out, { recursive: true });
const pwPath = process.env.PLAYWRIGHT_MODULE || 'playwright';
const { chromium } = await import(pwPath);
const data = JSON.parse(fs.readFileSync(path.join(root, 'database/demo/hotels.json'), 'utf8'));

function scene(name, tone, seed, accent) {
  const o = accent ? { accent } : {};
  switch (name) {
    case 'resort': return S.resort(seed, tone || 'day', o);
    case 'pool': return S.pool(seed, tone || 'day', o);
    case 'beach': return S.beach(seed, tone || 'day', o);
    case 'room': return S.room(seed, { tone: tone || 'day', accent });
    case 'lobby': return S.lobby(seed, o);
    case 'restaurant': return S.restaurant(seed, tone || 'golden', o);
    case 'spa': return S.spa(seed, o);
    case 'oldtown': return S.oldtown(seed, tone || 'golden', o);
    case 'lodge': return S.lodge(seed, tone || 'day', o);
    case 'city': return S.city(seed, tone || 'dusk', o);
    default: throw new Error('Bilinmeyen sahne: ' + name);
  }
}

const jobs = [];
for (const h of data.hotels) h.images.forEach(([n, t, seed], i) => jobs.push([`${h.slug}-${i + 1}.jpg`, scene(n, t, seed)]));
data.room_images.forEach(([n, t, seed, accent], i) => jobs.push([`room-${i + 1}.jpg`, scene(n, t, seed, accent)]));
jobs.push(['site-hero.jpg', S.resort(7001, 'golden')], ['site-login.jpg', S.beach(7002, 'golden', { straw: true })], ['site-support.jpg', S.lobby(7003)]);

const b = await chromium.launch();
const p = await b.newPage({ viewport: { width: S.W, height: S.H } });
for (const [file, svg] of jobs) {
  await p.setContent(`<html><body style="margin:0">${svg}</body></html>`);
  await p.screenshot({ path: path.join(out, file), type: 'jpeg', quality: 76 });
}
await b.close();
console.log(jobs.length + ' görsel üretildi → ' + out);
