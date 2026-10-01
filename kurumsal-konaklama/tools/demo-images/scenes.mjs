// Demo otel görselleri için SVG sahne üreteci (yalnız geliştirme aracı; üretim paketine girmez).
// Her sahne tohum (seed) ve palet ile çeşitlenir; çıktı 1600×1000 SVG metnidir.

export const W = 1600, H = 1000;

export function rng(seed) {
  let a = seed >>> 0;
  return function () {
    a = (a + 0x6D2B79F5) >>> 0;
    let t = a;
    t = Math.imul(t ^ (t >>> 15), t | 1);
    t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}
const rr = (r, a, b) => a + (b - a) * r();
const pick = (r, arr) => arr[Math.floor(r() * arr.length)];
const f = (n) => Math.round(n * 10) / 10;

export const SKIES = {
  day: { top: '#3f8fd6', mid: '#7fbde8', bottom: '#d6eef8', sun: '#fff6d8', sunGlow: 'rgba(255,248,220,.55)', sunY: 0.16, sea1: '#0e5f8a', sea2: '#1aa3b5', sea3: '#5fd3cf', haze: '#cfe6f2', light: 1 },
  golden: { top: '#4f86c6', mid: '#f2b880', bottom: '#fde3b8', sun: '#fff1c9', sunGlow: 'rgba(255,200,120,.6)', sunY: 0.36, sea1: '#14507a', sea2: '#2a8fa6', sea3: '#e9b98a', haze: '#f5d2a8', light: 0.92 },
  dusk: { top: '#1f2b55', mid: '#a5577a', bottom: '#f6a46c', sun: '#ffd9a0', sunGlow: 'rgba(255,150,90,.55)', sunY: 0.44, sea1: '#152a52', sea2: '#3b4f7c', sea3: '#e48b63', haze: '#c47a7f', light: 0.7 },
};

const defs = (s, extra = '') => `
<defs>
  <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${s.top}"/><stop offset=".55" stop-color="${s.mid}"/><stop offset="1" stop-color="${s.bottom}"/></linearGradient>
  <radialGradient id="sunglow"><stop offset="0" stop-color="${s.sunGlow}"/><stop offset="1" stop-color="rgba(255,255,255,0)"/></radialGradient>
  <linearGradient id="sea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${s.sea1}"/><stop offset=".6" stop-color="${s.sea2}"/><stop offset="1" stop-color="${s.sea3}"/></linearGradient>
  <linearGradient id="sand" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f3dfb6"/><stop offset="1" stop-color="#e2c38f"/></linearGradient>
  <linearGradient id="glass" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#a9d4ee"/><stop offset=".5" stop-color="#4f86b8"/><stop offset="1" stop-color="#2c4f7c"/></linearGradient>
  <linearGradient id="pool" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#43c6d8"/><stop offset="1" stop-color="#1590b5"/></linearGradient>
  <linearGradient id="trunk" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#8a6a4a"/><stop offset="1" stop-color="#5d4330"/></linearGradient>
  <filter id="blur6"><feGaussianBlur stdDeviation="6"/></filter>
  <filter id="blur14"><feGaussianBlur stdDeviation="14"/></filter>
  <filter id="blur30"><feGaussianBlur stdDeviation="30"/></filter>
  <filter id="soft"><feGaussianBlur stdDeviation="2.2"/></filter>
  <filter id="grain" x="0" y="0" width="100%" height="100%"><feTurbulence type="fractalNoise" baseFrequency=".9" numOctaves="2" seed="3"/><feColorMatrix type="saturate" values="0"/><feComponentTransfer><feFuncA type="table" tableValues="0 .06"/></feComponentTransfer></filter>
  ${extra}
</defs>`;

function sky(r, s, horizon) {
  const sx = rr(r, 0.2, 0.8) * W, sy = s.sunY * H;
  let o = `<rect width="${W}" height="${horizon + 2}" fill="url(#sky)"/>`;
  o += `<circle cx="${f(sx)}" cy="${f(sy)}" r="260" fill="url(#sunglow)"/>`;
  o += `<circle cx="${f(sx)}" cy="${f(sy)}" r="${s === SKIES.day ? 46 : 58}" fill="${s.sun}" opacity=".95"/>`;
  const n = Math.floor(rr(r, 3, 7));
  for (let i = 0; i < n; i++) {
    const cx = rr(r, -100, W + 100), cy = rr(r, 60, horizon * 0.55), w = rr(r, 160, 360);
    o += `<g opacity="${f(rr(r, .45, .85))}" filter="url(#blur14)" fill="${s === SKIES.dusk ? '#f3b8a0' : '#ffffff'}">` +
      `<ellipse cx="${f(cx)}" cy="${f(cy)}" rx="${f(w / 2)}" ry="${f(w / 7)}"/>` +
      `<ellipse cx="${f(cx - w / 5)}" cy="${f(cy - w / 12)}" rx="${f(w / 4)}" ry="${f(w / 8)}"/>` +
      `<ellipse cx="${f(cx + w / 6)}" cy="${f(cy - w / 14)}" rx="${f(w / 5)}" ry="${f(w / 9)}"/></g>`;
  }
  return o;
}

function ridge(r, base, amp, color, opacity = 1, step = 80) {
  let d = `M0 ${H} L0 ${f(base)}`;
  let y = base;
  for (let x = 0; x <= W + step; x += step) {
    y = Math.max(base - amp, Math.min(base + amp * 0.3, y + rr(r, -amp * 0.45, amp * 0.45)));
    d += ` L${x} ${f(y)}`;
  }
  d += ` L${W} ${H} Z`;
  return `<path d="${d}" fill="${color}" opacity="${opacity}"/>`;
}

function mountains(r, s, horizon) {
  const c1 = s === SKIES.dusk ? '#5b4a76' : s === SKIES.golden ? '#9a9ab8' : '#8fb3cf';
  const c2 = s === SKIES.dusk ? '#3e3a62' : s === SKIES.golden ? '#6f7aa0' : '#6a93b5';
  return ridge(r, horizon - 150, 90, c1, 0.75, 90) + ridge(r, horizon - 70, 70, c2, 0.85, 70);
}

function sea(r, s, top, bottom) {
  let o = `<rect y="${top}" width="${W}" height="${bottom - top}" fill="url(#sea)"/>`;
  for (let i = 0; i < 70; i++) {
    const y = rr(r, top + 6, bottom - 4), w = rr(r, 20, 140) * (0.4 + (y - top) / (bottom - top));
    o += `<rect x="${f(rr(r, 0, W))}" y="${f(y)}" width="${f(w)}" height="${f(rr(r, 1.2, 3))}" rx="1.5" fill="#fff" opacity="${f(rr(r, .12, .4))}"/>`;
  }
  return o;
}

function palm(r, x, y, h, lean = 0, dark = false) {
  const tx = x + lean * h * 0.35, ty = y - h;
  const leafC = dark ? ['#1f4d3a', '#2a6247', '#173d2e'] : ['#2f7d4f', '#3c9a5e', '#256b42'];
  let o = `<path d="M${f(x - 9)} ${f(y)} Q${f(x + lean * h * 0.15)} ${f(y - h * 0.55)} ${f(tx - 4)} ${f(ty)} L${f(tx + 4)} ${f(ty)} Q${f(x + 8 + lean * h * 0.15)} ${f(y - h * 0.55)} ${f(x + 9)} ${f(y)} Z" fill="url(#trunk)"/>`;
  for (let k = 0; k < 8; k++) {
    const yy = y - (h * k) / 8;
    o += `<path d="M${f(x - 9 + lean * h * 0.04 * k)} ${f(yy)} h18" stroke="#4b3626" stroke-width="2" opacity=".35"/>`;
  }
  const n = 9;
  for (let i = 0; i < n; i++) {
    const ang = (-170 + (i * 340) / (n - 1)) * Math.PI / 180;
    const len = h * rr(r, 0.42, 0.6);
    const ex = tx + Math.cos(ang) * len, ey = ty + Math.sin(ang) * len * 0.55 + len * 0.35;
    const cx = tx + Math.cos(ang) * len * 0.5, cy = ty - len * 0.28;
    o += `<path d="M${f(tx)} ${f(ty)} Q${f(cx)} ${f(cy)} ${f(ex)} ${f(ey)} Q${f(cx + 6)} ${f(cy + 22)} ${f(tx)} ${f(ty + 6)} Z" fill="${pick(r, leafC)}"/>`;
  }
  o += `<circle cx="${f(tx)}" cy="${f(ty + 6)}" r="7" fill="#6b4a2a"/>`;
  return o;
}

function umbrella(r, x, y, sc, color, straw = false) {
  const w = 70 * sc, h = 22 * sc;
  let o = `<line x1="${f(x)}" y1="${f(y)}" x2="${f(x)}" y2="${f(y - 60 * sc)}" stroke="#6b5440" stroke-width="${f(2.5 * sc)}"/>`;
  if (straw) {
    o += `<path d="M${f(x - w / 2)} ${f(y - 52 * sc)} L${f(x)} ${f(y - 52 * sc - h * 1.5)} L${f(x + w / 2)} ${f(y - 52 * sc)} Z" fill="#c9a46a"/>`;
    for (let i = 0; i < 7; i++) o += `<line x1="${f(x)}" y1="${f(y - 52 * sc - h * 1.5)}" x2="${f(x - w / 2 + (i * w) / 6)}" y2="${f(y - 52 * sc + 3 * sc)}" stroke="#a9834d" stroke-width="${f(1.2 * sc)}"/>`;
  } else {
    o += `<path d="M${f(x - w / 2)} ${f(y - 52 * sc)} Q${f(x)} ${f(y - 52 * sc - h * 2)} ${f(x + w / 2)} ${f(y - 52 * sc)} Z" fill="${color}"/>`;
    o += `<path d="M${f(x - w / 2)} ${f(y - 52 * sc)} Q${f(x - w / 4)} ${f(y - 48 * sc)} ${f(x)} ${f(y - 52 * sc)} Q${f(x + w / 4)} ${f(y - 48 * sc)} ${f(x + w / 2)} ${f(y - 52 * sc)}" fill="none" stroke="#fff" stroke-opacity=".5" stroke-width="${f(1.5 * sc)}"/>`;
  }
  return o;
}

function lounger(x, y, sc, color = '#fff') {
  return `<g transform="translate(${f(x)} ${f(y)}) scale(${f(sc)})"><rect x="-30" y="-6" width="52" height="7" rx="2" fill="${color}"/><path d="M22 -6 L36 -20 L40 -18 L27 -4 Z" fill="${color}"/><rect x="-28" y="1" width="3" height="7" fill="#9aa"/><rect x="18" y="1" width="3" height="7" fill="#9aa"/></g>`;
}

function shade(x, y, w, h, op = .22) {
  return `<ellipse cx="${f(x)}" cy="${f(y)}" rx="${f(w)}" ry="${f(h)}" fill="#0d2238" opacity="${op}" filter="url(#blur6)"/>`;
}

// Çok katlı resort binası (ön yüz + yan yüz, balkon ızgarası)
function building(r, x, baseY, w, floors, opts = {}) {
  const fh = opts.floorH || 48, h = floors * fh, side = opts.side ?? w * 0.18;
  const facade = opts.facade || pick(r, ['#fbf7ef', '#f4ede1', '#f7f3ea', '#fffaf2']);
  const accent = opts.accent || pick(r, ['#c8a46a', '#8fb7c9', '#d58a64', '#9cb48a']);
  const top = baseY - h;
  let o = shade(x + w / 2 + 40, baseY + 6, w * 0.62, 18, .25);
  // yan yüz
  o += `<path d="M${f(x + w)} ${f(top)} L${f(x + w + side)} ${f(top + side * 0.35)} L${f(x + w + side)} ${f(baseY)} L${f(x + w)} ${f(baseY)} Z" fill="${facade}" />`;
  o += `<path d="M${f(x + w)} ${f(top)} L${f(x + w + side)} ${f(top + side * 0.35)} L${f(x + w + side)} ${f(baseY)} L${f(x + w)} ${f(baseY)} Z" fill="#1d3557" opacity=".18"/>`;
  // ön yüz
  o += `<rect x="${f(x)}" y="${f(top)}" width="${f(w)}" height="${f(h)}" fill="${facade}"/>`;
  o += `<rect x="${f(x)}" y="${f(top)}" width="${f(w)}" height="${f(h)}" fill="url(#facadeShade)" opacity=".35"/>`;
  // çatı
  o += `<rect x="${f(x - 8)}" y="${f(top - 14)}" width="${f(w + 16)}" height="14" fill="${accent}"/>`;
  o += `<path d="M${f(x + w + 8)} ${f(top - 14)} L${f(x + w + side + 6)} ${f(top - 14 + side * 0.35)} L${f(x + w + side + 6)} ${f(top + side * 0.35)} L${f(x + w + 8)} ${f(top)} Z" fill="${accent}" opacity=".8"/>`;
  // balkonlar
  const cols = opts.cols || Math.max(6, Math.round(w / 64));
  const cw = w / cols;
  for (let fl = 0; fl < floors; fl++) {
    const y = top + fl * fh;
    for (let c = 0; c < cols; c++) {
      const bx = x + c * cw;
      const lit = opts.night && r() < 0.55;
      o += `<rect x="${f(bx + cw * 0.14)}" y="${f(y + fh * 0.16)}" width="${f(cw * 0.72)}" height="${f(fh * 0.58)}" rx="2" fill="${lit ? '#ffd58a' : 'url(#glass)'}" opacity="${lit ? .95 : .92}"/>`;
      if (!opts.night) o += `<rect x="${f(bx + cw * 0.14)}" y="${f(y + fh * 0.16)}" width="${f(cw * 0.25)}" height="${f(fh * 0.58)}" fill="#fff" opacity=".18"/>`;
      o += `<rect x="${f(bx + cw * 0.08)}" y="${f(y + fh * 0.62)}" width="${f(cw * 0.84)}" height="${f(fh * 0.16)}" fill="${facade}" opacity=".9"/>`;
      o += `<line x1="${f(bx + cw * 0.08)}" y1="${f(y + fh * 0.62)}" x2="${f(bx + cw * 0.92)}" y2="${f(y + fh * 0.62)}" stroke="#7c8ea3" stroke-width="1.4" opacity=".7"/>`;
    }
    o += `<rect x="${f(x)}" y="${f(y + fh * 0.78)}" width="${f(w)}" height="${f(fh * 0.07)}" fill="#1d3557" opacity=".12"/>`;
    // yan yüz pencereleri
    for (let c = 0; c < 2; c++) {
      const sx = x + w + side * (0.2 + c * 0.42), sy = y + fh * 0.2 + side * 0.35 * (0.2 + c * 0.42);
      o += `<path d="M${f(sx)} ${f(sy)} l${f(side * 0.28)} ${f(side * 0.1)} v${f(fh * 0.5)} l${f(-side * 0.28)} ${f(-side * 0.1)} Z" fill="${opts.night && r() < .5 ? '#ffd58a' : '#557ea6'}" opacity=".75"/>`;
    }
  }
  // giriş saçağı
  if (opts.entrance !== false) {
    const ex = x + w * rr(r, 0.35, 0.55);
    o += `<rect x="${f(ex - 70)}" y="${f(baseY - fh * 0.9)}" width="140" height="${f(fh * 0.9)}" fill="#2b3f5c" opacity=".85"/>`;
    o += `<rect x="${f(ex - 90)}" y="${f(baseY - fh * 1.05)}" width="180" height="12" fill="${accent}"/>`;
    o += `<rect x="${f(ex - 60)}" y="${f(baseY - fh * 0.8)}" width="120" height="${f(fh * 0.8)}" fill="${opts.night ? '#ffcf7a' : '#9cc6e0'}" opacity=".85"/>`;
  }
  return o;
}

function hedgeRow(r, y, h, color = '#3f8a52') {
  let o = '';
  for (let x = -40; x < W + 40; x += rr(r, 40, 80)) {
    o += `<ellipse cx="${f(x)}" cy="${f(y)}" rx="${f(rr(r, 40, 70))}" ry="${f(h * rr(r, .7, 1.1))}" fill="${color}" opacity="${f(rr(r, .85, 1))}"/>`;
  }
  return o;
}

function flowers(r, y0, y1, n, colors) {
  let o = '';
  for (let i = 0; i < n; i++) o += `<circle cx="${f(rr(r, 0, W))}" cy="${f(rr(r, y0, y1))}" r="${f(rr(r, 3, 7))}" fill="${pick(r, colors)}" opacity=".9"/>`;
  return o;
}

const vignette = `<rect width="${W}" height="${H}" fill="url(#vig)"/><rect width="${W}" height="${H}" filter="url(#grain)" opacity=".7"/>`;
const vigDef = `<radialGradient id="vig" cx=".5" cy=".5" r=".75"><stop offset=".6" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#0b1a2c" stop-opacity=".35"/></radialGradient>
<linearGradient id="facadeShade" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fff"/><stop offset="1" stop-color="#9fb2c6"/></linearGradient>`;

const svg = (inner, s, extra = '') => `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${H}" width="${W}" height="${H}">${defs(s, vigDef + extra)}${inner}${vignette}</svg>`;

// ---------- SAHNELER ----------

export function resort(seed, tone = 'day', o = {}) {
  const r = rng(seed), s = SKIES[tone];
  const horizon = 470;
  let g = sky(r, s, horizon) + mountains(r, s, horizon);
  g += sea(r, s, horizon, 600);
  g += `<rect y="590" width="${W}" height="60" fill="url(#sand)"/>`;
  g += `<rect y="640" width="${W}" height="${H - 640}" fill="#5b9c5e"/>`;
  g += `<rect y="640" width="${W}" height="${H - 640}" fill="url(#lawn)"/>`;
  const bw = rr(r, 760, 980), bx = rr(r, 120, W - bw - 260);
  const floors = o.floors || Math.floor(rr(r, 6, 9));
  g += palm(r, bx - 60, 640, 260, -0.3, tone === 'dusk');
  g += building(r, bx, 640, bw, floors, { accent: o.accent, night: tone === 'dusk' });
  if (r() < 0.7) g += building(r, bx + bw + 120, 640, 220, Math.max(3, floors - 3), { cols: 4, entrance: false, accent: o.accent, night: tone === 'dusk' });
  g += hedgeRow(r, 650, 22, '#2f6f45');
  // havuz
  g += `<path d="M120 760 Q 300 700 760 712 Q 1180 720 1460 770 Q 1500 840 1380 880 Q 900 920 360 900 Q 120 880 120 760 Z" fill="#e9eef2"/>`;
  g += `<path d="M160 768 Q 330 718 760 728 Q 1160 736 1420 778 Q 1450 834 1350 866 Q 900 902 380 884 Q 170 866 160 768 Z" fill="url(#pool)"/>`;
  for (let i = 0; i < 26; i++) {
    const x = rr(r, 220, 1360), y = rr(r, 740, 870);
    g += `<path d="M${f(x)} ${f(y)} q 14 -6 28 0 t 28 0" fill="none" stroke="#bff3f6" stroke-width="2" opacity="${f(rr(r, .3, .7))}"/>`;
  }
  const uc = o.umbrella || pick(r, ['#e8eef3', '#f0c987', '#e98a6b', '#2f6f9f']);
  for (let i = 0; i < 7; i++) {
    const x = 180 + i * 190 + rr(r, -20, 20);
    g += lounger(x, 945, 1.4) + lounger(x + 70, 945, 1.4) + umbrella(r, x + 34, 950, 1.5, uc);
  }
  g += palm(r, 60, 1000, 380, 0.25, tone === 'dusk') + palm(r, 1540, 1000, 420, -0.3, tone === 'dusk');
  g += flowers(r, 655, 690, 60, ['#e85d75', '#f2a541', '#ffffff', '#c86bfa']);
  if (tone === 'dusk') g += `<rect width="${W}" height="${H}" fill="#1b1d4a" opacity=".22"/>`;
  return svg(g, s, `<linearGradient id="lawn" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#4f9a5b"/><stop offset="1" stop-color="#2f6f45"/></linearGradient>`);
}

export function pool(seed, tone = 'day', o = {}) {
  const r = rng(seed), s = SKIES[tone];
  const horizon = 380;
  let g = sky(r, s, horizon) + mountains(r, s, horizon);
  g += `<rect y="${horizon}" width="${W}" height="${H - horizon}" fill="#e9e2d3"/>`;
  g += building(r, 260, 520, 1080, 3, { floorH: 46, accent: o.accent, night: tone === 'dusk' });
  g += hedgeRow(r, 530, 26, '#2f6f45');
  for (let i = 0; i < 4; i++) g += palm(r, 120 + i * 440 + rr(r, -40, 40), 560, 300, rr(r, -.3, .3), tone === 'dusk');
  // perspektif havuz
  g += `<path d="M280 600 L1320 600 L1560 1000 L40 1000 Z" fill="#f4f1ea"/>`;
  g += `<path d="M330 620 L1270 620 L1480 1000 L120 1000 Z" fill="url(#pool)"/>`;
  g += `<path d="M330 620 L1270 620 L1290 640 L310 640 Z" fill="#0d6f93" opacity=".35"/>`;
  for (let i = 0; i < 55; i++) {
    const y = rr(r, 640, 990), t = (y - 620) / 380, x = rr(r, 330 - 210 * t, 1270 + 210 * t - 60);
    g += `<path d="M${f(x)} ${f(y)} q ${f(12 + 20 * t)} ${f(-5 - 4 * t)} ${f(24 + 40 * t)} 0 t ${f(24 + 40 * t)} 0" fill="none" stroke="#d9fbff" stroke-width="${f(1.5 + 2 * t)}" opacity="${f(rr(r, .25, .6))}"/>`;
  }
  // havuz kenarı şezlonglar
  const uc = o.umbrella || pick(r, ['#f3efe6', '#e7b874', '#2f6f9f', '#d96f5a']);
  for (let i = 0; i < 5; i++) {
    const y = 640 + i * 80, sc = 0.9 + i * 0.35;
    const xl = 300 - (y - 620) * 0.55 - 40 * sc, xr = 1300 + (y - 620) * 0.55 + 40 * sc;
    g += lounger(xl - 30, y + 30, sc) + lounger(xr + 20, y + 30, sc);
    if (i % 2 === 0) g += umbrella(r, xl - 10, y + 36, sc, uc) + umbrella(r, xr + 40, y + 36, sc, uc);
  }
  if (tone === 'dusk') g += `<rect width="${W}" height="${H}" fill="#1b1d4a" opacity=".2"/>`;
  return svg(g, s);
}

export function beach(seed, tone = 'day', o = {}) {
  const r = rng(seed), s = SKIES[tone];
  const horizon = 430;
  let g = sky(r, s, horizon) + mountains(r, s, horizon);
  g += sea(r, s, horizon, 720);
  // dalga kıyısı
  g += `<path d="M0 700 Q 400 680 800 705 T 1600 690 L1600 ${H} L0 ${H} Z" fill="url(#sand)"/>`;
  g += `<path d="M0 700 Q 400 680 800 705 T 1600 690" fill="none" stroke="#fff" stroke-width="6" opacity=".7"/>`;
  // iskele
  if (r() < 0.8) {
    const px = rr(r, 900, 1300);
    g += `<path d="M${f(px)} 720 L${f(px + 30)} 720 L${f(px - 60)} 520 L${f(px - 72)} 520 Z" fill="#a7825a"/>`;
    g += `<rect x="${f(px - 160)}" y="505" width="160" height="22" fill="#a7825a"/>`;
    for (let i = 0; i < 6; i++) g += lounger(px - 140 + i * 24, 505, 0.5);
  }
  const straw = o.straw ?? r() < 0.5;
  for (let row = 0; row < 3; row++) {
    const y = 760 + row * 95, sc = 1 + row * 0.45, gap = 210 + row * 70;
    for (let x = rr(r, -40, 60); x < W + 60; x += gap) {
      g += shade(x + 30, y + 6, 60 * sc, 9 * sc, .2) + lounger(x - 10, y, sc) + lounger(x + 60 * sc, y, sc) + umbrella(r, x + 28 * sc, y + 4, sc * 1.1, o.umbrella || '#f2efe8', straw);
    }
  }
  g += palm(r, 1500, 1000, 460, -0.35, tone === 'dusk');
  if (tone === 'dusk') g += `<rect width="${W}" height="${H}" fill="#1b1d4a" opacity=".18"/>`;
  return svg(g, s);
}

export function room(seed, o = {}) {
  const r = rng(seed), s = SKIES[o.tone || 'day'];
  const wall = o.wall || pick(r, ['#efe7dc', '#e9eef0', '#f2ece4', '#e8e2d8']);
  const accent = o.accent || pick(r, ['#2f6f9f', '#c87e4f', '#6f8f5e', '#8a5a83', '#c9a24b']);
  const wood = pick(r, ['#b98f63', '#a77a4f', '#c9a47a']);
  let g = `<rect width="${W}" height="${H}" fill="${wall}"/>`;
  g += `<rect width="${W}" height="${H}" fill="url(#wallLight)"/>`;
  // pencere / balkon kapısı (deniz manzarası)
  const wx = 860, wy = 120, ww = 620, wh = 600;
  g += `<svg x="${wx}" y="${wy}" width="${ww}" height="${wh}" viewBox="0 0 ${W} ${H}" preserveAspectRatio="xMidYMid slice">` +
    `<rect width="${W}" height="520" fill="url(#sky)"/><circle cx="1100" cy="${s.sunY * H}" r="70" fill="${s.sun}"/>` +
    ridge(rng(seed + 9), 470, 70, '#7ea6c6', .8) + `<rect y="500" width="${W}" height="500" fill="url(#sea)"/>` +
    sea(rng(seed + 3), s, 500, 1000) + `</svg>`;
  g += `<rect x="${wx}" y="${wy}" width="${ww}" height="${wh}" fill="none" stroke="#fdfbf7" stroke-width="16"/>`;
  g += `<line x1="${wx + ww / 2}" y1="${wy}" x2="${wx + ww / 2}" y2="${wy + wh}" stroke="#fdfbf7" stroke-width="10"/>`;
  g += `<rect x="${wx}" y="${wy + wh * .7}" width="${ww}" height="6" fill="#fdfbf7" opacity=".8"/>`;
  for (let i = 0; i < 9; i++) g += `<line x1="${wx + 10 + i * (ww - 20) / 8}" y1="${wy + wh * .7}" x2="${wx + 10 + i * (ww - 20) / 8}" y2="${wy + wh}" stroke="#fdfbf7" stroke-width="4" opacity=".8"/>`;
  // perdeler
  g += `<path d="M${wx - 70} 90 Q ${wx - 40} 420 ${wx - 90} 760 L${wx + 30} 760 Q ${wx + 50} 420 ${wx + 20} 90 Z" fill="${accent}" opacity=".55"/>`;
  g += `<path d="M${wx + ww - 20} 90 Q ${wx + ww + 10} 420 ${wx + ww - 30} 760 L${wx + ww + 90} 760 Q ${wx + ww + 60} 420 ${wx + ww + 70} 90 Z" fill="${accent}" opacity=".55"/>`;
  g += `<rect x="${wx - 100}" y="80" width="${ww + 200}" height="12" rx="6" fill="#8d7a66"/>`;
  // zemin
  g += `<path d="M0 760 L${W} 760 L${W} ${H} L0 ${H} Z" fill="${wood}"/>`;
  for (let i = -20; i < 40; i++) g += `<line x1="${f(800 + i * 40)}" y1="760" x2="${f(800 + i * 120)}" y2="${H}" stroke="#000" stroke-opacity=".08" stroke-width="2"/>`;
  g += `<rect y="760" width="${W}" height="${H - 760}" fill="url(#floorShine)"/>`;
  // halı
  g += `<path d="M120 860 L820 860 L900 990 L40 990 Z" fill="#e9e2d6" opacity=".95"/>`;
  // yatak başlığı
  g += `<rect x="70" y="330" width="720" height="300" rx="14" fill="${accent}"/>`;
  for (let i = 0; i < 6; i++) g += `<rect x="${86 + i * 116}" y="346" width="104" height="268" rx="10" fill="#fff" opacity=".07"/>`;
  // yatak
  g += shade(430, 905, 420, 30, .28);
  g += `<path d="M40 640 L820 640 L860 900 L0 900 Z" fill="#fbfaf7"/>`;
  g += `<path d="M0 800 L860 800 L860 900 L0 900 Z" fill="#ece8e1"/>`;
  g += `<path d="M30 720 L840 720 L860 800 L0 800 Z" fill="${accent}" opacity=".85"/>`;
  g += `<path d="M30 720 L840 720 L845 735 L25 735 Z" fill="#fff" opacity=".25"/>`;
  // yastıklar
  for (const [x, c] of [[110, '#ffffff'], [300, '#ffffff'], [490, '#ffffff'], [180, accent], [420, '#e9dcc7']]) {
    g += `<rect x="${x}" y="${c === '#ffffff' ? 560 : 600}" width="${c === '#ffffff' ? 200 : 140}" height="${c === '#ffffff' ? 100 : 80}" rx="26" fill="${c}" ${c === accent ? 'opacity=".9"' : ''}/>`;
  }
  // komodin + lamba
  g += `<rect x="-40" y="660" width="90" height="140" fill="${wood}"/><rect x="800" y="660" width="0" height="0"/>`;
  g += `<circle cx="760" cy="420" r="120" fill="#ffe2a8" opacity=".35" filter="url(#blur30)"/>`;
  g += `<rect x="740" y="470" width="40" height="190" fill="#a28f78"/><path d="M700 470 L820 470 L800 400 L720 400 Z" fill="#f5ead6"/>`;
  // tablo
  g += `<rect x="230" y="150" width="360" height="140" fill="#fdfbf7" stroke="#b9a68d" stroke-width="8"/>`;
  g += `<path d="M240 270 Q 330 200 410 250 T 580 220 L580 280 L240 280 Z" fill="${accent}" opacity=".6"/>`;
  // bitki
  g += `<rect x="1490" y="840" width="70" height="90" rx="8" fill="#d8cdbd"/>`;
  for (let i = 0; i < 9; i++) g += `<path d="M1525 845 q ${f(rr(r, -80, 80))} ${f(rr(r, -160, -90))} ${f(rr(r, -60, 60))} ${f(rr(r, -230, -160))}" stroke="#3d7a4e" stroke-width="10" fill="none" stroke-linecap="round"/>`;
  return svg(g, s, `<linearGradient id="wallLight" x1="1" y1="0" x2="0" y2="0"><stop offset="0" stop-color="#fff" stop-opacity=".35"/><stop offset="1" stop-color="#000" stop-opacity=".08"/></linearGradient>
  <linearGradient id="floorShine" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".18"/><stop offset="1" stop-color="#000" stop-opacity=".15"/></linearGradient>`);
}

export function lobby(seed, o = {}) {
  const r = rng(seed), s = SKIES.day;
  const accent = o.accent || pick(r, ['#c9a24b', '#2f6f9f', '#a05a3c']);
  let g = `<rect width="${W}" height="${H}" fill="#efe8dc"/>`;
  // arka cam cephe
  g += `<rect x="300" y="120" width="1000" height="560" fill="url(#sky)"/>`;
  g += ridge(r, 560, 60, '#8fb3cf', .9) + `<rect x="300" y="590" width="1000" height="90" fill="url(#sea)"/>`;
  g += `<rect x="0" y="0" width="300" height="${H}" fill="#e7dfd1"/><rect x="1300" y="0" width="300" height="${H}" fill="#e2d9ca"/>`;
  for (let i = 0; i <= 5; i++) g += `<rect x="${296 + i * 200}" y="120" width="8" height="560" fill="#3a3a3a" opacity=".8"/>`;
  g += `<rect x="300" y="116" width="1000" height="8" fill="#3a3a3a"/>`;
  // tavan
  g += `<rect width="${W}" height="110" fill="#f6f2ea"/><rect y="104" width="${W}" height="8" fill="${accent}" opacity=".6"/>`;
  // avize/ışıklar
  for (let i = 0; i < 6; i++) {
    const x = 220 + i * 230;
    g += `<line x1="${x}" y1="110" x2="${x}" y2="${f(rr(r, 200, 260))}" stroke="#8a7a62" stroke-width="2"/>`;
    g += `<circle cx="${x}" cy="270" r="70" fill="#ffd98a" opacity=".35" filter="url(#blur14)"/><ellipse cx="${x}" cy="262" rx="26" ry="18" fill="#fff3d6"/>`;
  }
  // mermer zemin
  g += `<rect y="680" width="${W}" height="${H - 680}" fill="url(#marble)"/>`;
  for (let i = -10; i < 30; i++) g += `<line x1="${f(800 + i * 70)}" y1="680" x2="${f(800 + i * 220)}" y2="${H}" stroke="#b8ad9b" stroke-opacity=".35" stroke-width="2"/>`;
  for (let i = 0; i < 6; i++) g += `<line x1="0" y1="${f(680 + i * i * 9 + i * 14)}" x2="${W}" y2="${f(680 + i * i * 9 + i * 14)}" stroke="#b8ad9b" stroke-opacity=".3"/>`;
  // yansıma
  g += `<rect x="300" y="690" width="1000" height="160" fill="url(#sky)" opacity=".18" filter="url(#blur6)"/>`;
  // sütunlar
  for (const x of [160, 1380]) {
    g += `<rect x="${x}" y="110" width="70" height="580" fill="#f8f4ec"/><rect x="${x + 50}" y="110" width="20" height="580" fill="#000" opacity=".06"/>`;
    g += `<rect x="${x - 10}" y="680" width="90" height="20" fill="#e5dccd"/>`;
  }
  // resepsiyon
  g += shade(800, 870, 380, 26, .25);
  g += `<path d="M450 760 L1150 760 L1180 870 L420 870 Z" fill="${accent}"/>`;
  g += `<rect x="440" y="744" width="720" height="22" rx="4" fill="#3b3a38"/>`;
  for (let i = 0; i < 8; i++) g += `<rect x="${470 + i * 86}" y="780" width="60" height="80" fill="#fff" opacity=".08"/>`;
  // koltuklar ve bitkiler
  for (const x of [120, 1330]) {
    g += shade(x + 80, 960, 130, 14, .25) + `<rect x="${x}" y="880" width="170" height="70" rx="16" fill="#6a7f8e"/><rect x="${x}" y="850" width="170" height="40" rx="14" fill="#7f95a4"/>`;
  }
  for (const x of [330, 1250]) {
    g += `<path d="M${x - 30} 760 L${x + 30} 760 L${x + 22} 680 L${x - 22} 680 Z" fill="#d6cbb8"/>`;
    for (let i = 0; i < 10; i++) g += `<path d="M${x} 690 q ${f(rr(r, -90, 90))} ${f(rr(r, -140, -60))} ${f(rr(r, -70, 70))} ${f(rr(r, -260, -150))}" stroke="#3d7a4e" stroke-width="12" fill="none" stroke-linecap="round"/>`;
  }
  return svg(g, s, `<linearGradient id="marble" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f3eee5"/><stop offset="1" stop-color="#d9d0c1"/></linearGradient>`);
}

export function restaurant(seed, tone = 'golden', o = {}) {
  const r = rng(seed), s = SKIES[tone];
  const horizon = 520;
  let g = sky(r, s, horizon) + mountains(r, s, horizon) + sea(r, s, horizon, 700);
  // teras zemini
  g += `<path d="M0 640 L${W} 640 L${W} ${H} L0 ${H} Z" fill="#cdb79a"/>`;
  for (let i = 0; i < 18; i++) g += `<line x1="0" y1="${640 + i * i * 1.2 + i * 6}" x2="${W}" y2="${640 + i * i * 1.2 + i * 6}" stroke="#a78f70" stroke-opacity=".35"/>`;
  g += `<rect y="620" width="${W}" height="24" fill="#f3efe7"/>`;
  for (let i = 0; i < 40; i++) g += `<rect x="${i * 40 + 6}" y="560" width="6" height="64" fill="#f3efe7"/>`;
  g += `<rect y="556" width="${W}" height="8" fill="#f3efe7"/>`;
  // ışık zinciri
  g += `<path d="M0 120 Q 400 260 800 150 T 1600 140" fill="none" stroke="#333" stroke-width="2"/>`;
  for (let i = 0; i <= 24; i++) {
    const t = i / 24, x = t * W, y = t < .5 ? 120 + Math.sin(t * 2 * Math.PI) * 70 + 40 * (1 - Math.abs(.25 - t) * 4) : 150 - Math.sin((t - .5) * 2 * Math.PI) * 30;
    g += `<circle cx="${f(x)}" cy="${f(y + 14)}" r="18" fill="#ffd27a" opacity=".45" filter="url(#blur6)"/><circle cx="${f(x)}" cy="${f(y + 14)}" r="6" fill="#fff4cf"/>`;
  }
  // masalar
  const cloth = o.accent ? '#fbf8f2' : '#fbf8f2';
  for (let row = 0; row < 2; row++) {
    for (let i = 0; i < (row ? 3 : 4); i++) {
      const sc = row ? 1.6 : 1, x = row ? 220 + i * 560 : 160 + i * 400, y = row ? 900 : 720;
      g += shade(x, y + 70 * sc, 120 * sc, 14 * sc, .25);
      g += `<ellipse cx="${x}" cy="${y}" rx="${100 * sc}" ry="${26 * sc}" fill="${cloth}"/><path d="M${x - 100 * sc} ${y} L${x - 92 * sc} ${y + 60 * sc} L${x + 92 * sc} ${y + 60 * sc} L${x + 100 * sc} ${y} Z" fill="${cloth}"/>`;
      g += `<path d="M${x - 92 * sc} ${y + 60 * sc} L${x + 92 * sc} ${y + 60 * sc}" stroke="#e6dfd2" stroke-width="${3 * sc}"/>`;
      g += `<rect x="${x - 6 * sc}" y="${y - 30 * sc}" width="${12 * sc}" height="${24 * sc}" rx="${3 * sc}" fill="#fff3d6"/><circle cx="${x}" cy="${y - 34 * sc}" r="${14 * sc}" fill="#ffcf70" opacity=".5" filter="url(#blur6)"/>`;
      for (const dx of [-150, 150]) g += `<rect x="${x + dx * sc - 26 * sc}" y="${y - 30 * sc}" width="${52 * sc}" height="${100 * sc}" rx="${8 * sc}" fill="${o.accent || '#5a6f80'}"/>`;
    }
  }
  if (tone === 'dusk') g += `<rect width="${W}" height="${H}" fill="#1b1d4a" opacity=".15"/>`;
  return svg(g, s);
}

export function spa(seed, o = {}) {
  const r = rng(seed), s = SKIES.day;
  const accent = o.accent || pick(r, ['#c9a24b', '#9bc4c4', '#c98a6a']);
  let g = `<rect width="${W}" height="${H}" fill="#2a2621"/>`;
  // taş duvar
  for (let y = 0; y < 640; y += 46) for (let x = (y / 46) % 2 ? -60 : 0; x < W; x += 120) g += `<rect x="${x + 2}" y="${y + 2}" width="116" height="42" rx="4" fill="#4a4036" opacity="${f(rr(r, .55, 1))}"/>`;
  // nişler ve mumlar
  for (let i = 0; i < 5; i++) {
    const x = 160 + i * 320;
    g += `<rect x="${x - 50}" y="200" width="100" height="160" rx="50" fill="#1c1916"/>`;
    g += `<circle cx="${x}" cy="300" r="80" fill="#ffb85c" opacity=".35" filter="url(#blur14)"/><rect x="${x - 8}" y="310" width="16" height="40" fill="#f8eedb"/><ellipse cx="${x}" cy="304" rx="5" ry="9" fill="#ffd58a"/>`;
  }
  // kapalı havuz
  g += `<rect y="600" width="${W}" height="${H - 600}" fill="#3a332b"/>`;
  g += `<path d="M180 640 L1420 640 L1560 980 L40 980 Z" fill="url(#spaPool)"/>`;
  for (let i = 0; i < 40; i++) {
    const y = rr(r, 660, 970), t = (y - 640) / 340, x = rr(r, 180 - 140 * t, 1420 + 140 * t - 80);
    g += `<path d="M${f(x)} ${f(y)} q 20 -6 40 0 t 40 0" fill="none" stroke="#bdf2ee" stroke-width="2" opacity="${f(rr(r, .15, .45))}"/>`;
  }
  for (let i = 0; i < 5; i++) g += `<ellipse cx="${160 + i * 320}" cy="${f(800 + rr(r, -40, 60))}" rx="70" ry="22" fill="#ffc26e" opacity=".22" filter="url(#blur14)"/>`;
  g += `<rect x="0" y="600" width="${W}" height="40" fill="${accent}" opacity=".5"/>`;
  return svg(g, s, `<linearGradient id="spaPool" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#1e7c86"/><stop offset="1" stop-color="#0f4a57"/></linearGradient>`);
}

export function oldtown(seed, tone = 'golden', o = {}) {
  const r = rng(seed), s = SKIES[tone];
  let g = `<rect width="${W}" height="420" fill="url(#sky)"/>`;
  g += `<circle cx="1200" cy="140" r="200" fill="url(#sunglow)"/>`;
  // sol bina (taş zemin + ahşap cumba)
  const stone = pick(r, ['#d9c7a7', '#e2d2b4', '#cdb89a']);
  g += `<rect x="0" y="80" width="640" height="${H}" fill="${stone}"/>`;
  for (let y = 520; y < H; y += 34) for (let x = (y / 34) % 2 ? -40 : 0; x < 640; x += 80) g += `<rect x="${x + 2}" y="${y + 2}" width="76" height="30" rx="3" fill="#000" opacity="${f(rr(r, .03, .1))}"/>`;
  g += `<rect x="40" y="200" width="620" height="300" fill="#8a5a3a"/><rect x="40" y="186" width="640" height="20" fill="#6e4329"/>`;
  for (let i = 0; i < 4; i++) {
    g += `<rect x="${70 + i * 150}" y="236" width="110" height="200" fill="${tone === 'dusk' ? '#ffd58a' : '#cfe2ec'}" opacity=".9"/>`;
    g += `<line x1="${125 + i * 150}" y1="236" x2="${125 + i * 150}" y2="436" stroke="#6e4329" stroke-width="6"/><line x1="${70 + i * 150}" y1="320" x2="${180 + i * 150}" y2="320" stroke="#6e4329" stroke-width="6"/>`;
  }
  for (let i = 0; i < 10; i++) g += `<line x1="${50 + i * 62}" y1="500" x2="${30 + i * 62}" y2="540" stroke="#6e4329" stroke-width="8"/>`;
  // kemerli kapı
  g += `<path d="M180 ${H} L180 700 Q 270 600 360 700 L360 ${H} Z" fill="#5a3a26"/><path d="M196 ${H} L196 706 Q 270 622 344 706 L344 ${H} Z" fill="#6e4a32"/>`;
  // begonvil
  for (let i = 0; i < 260; i++) {
    const x = rr(r, 380, 760), y = rr(r, 480, 760) + (x - 380) * 0.15;
    g += `<circle cx="${f(x)}" cy="${f(y)}" r="${f(rr(r, 5, 13))}" fill="${pick(r, ['#d63a7a', '#e5559a', '#b82a63', '#f07fb4'])}" opacity=".92"/>`;
  }
  for (let i = 0; i < 60; i++) g += `<circle cx="${f(rr(r, 380, 760))}" cy="${f(rr(r, 480, 820))}" r="${f(rr(r, 6, 12))}" fill="#3f7a46" opacity=".85"/>`;
  // sağ bina
  g += `<rect x="1060" y="160" width="540" height="${H}" fill="${pick(r, ['#efe3cf', '#e8d6bb'])}"/>`;
  g += `<rect x="1060" y="160" width="540" height="${H}" fill="#000" opacity=".08"/>`;
  for (let i = 0; i < 3; i++) for (let j = 0; j < 2; j++) {
    g += `<rect x="${1110 + i * 160}" y="${240 + j * 220}" width="90" height="130" fill="${tone === 'dusk' ? '#ffd58a' : '#3f5b74'}" opacity=".85"/><rect x="${1100 + i * 160}" y="${236 + j * 220}" width="110" height="10" fill="#7a5a3a"/>`;
    g += `<rect x="${1094 + i * 160}" y="${240 + j * 220}" width="16" height="130" fill="#4f7a5a"/><rect x="${1200 + i * 160}" y="${240 + j * 220}" width="16" height="130" fill="#4f7a5a"/>`;
  }
  // fenerler
  // arka liman ve deniz
  g += `<rect x="640" y="430" width="420" height="190" fill="url(#sea)"/>`;
  g += `<path d="M640 432 L700 400 L760 418 L830 380 L900 410 L960 392 L1060 420 L1060 436 L640 436 Z" fill="#8fa9c2" opacity=".9"/>`;
  for (let i = 0; i < 18; i++) g += `<rect x="${f(rr(r, 650, 1020))}" y="${f(rr(r, 450, 610))}" width="${f(rr(r, 14, 50))}" height="2" fill="#fff" opacity=".35"/>`;
  // arnavut kaldırımı
  g += `<path d="M640 620 L1060 620 L1360 ${H} L400 ${H} Z" fill="#b8ab98"/>`;
  for (let i = 0; i < 200; i++) {
    const y = rr(r, 625, H), t = (y - 620) / 380, x = rr(r, 640 - 240 * t, 1060 + 300 * t);
    g += `<ellipse cx="${f(x)}" cy="${f(y)}" rx="${f(6 + 18 * t)}" ry="${f(3 + 8 * t)}" fill="#8f826f" opacity=".45"/>`;
  }
  for (const x of [700, 1040]) g += `<circle cx="${x}" cy="420" r="40" fill="#ffcf70" opacity=".45" filter="url(#blur14)"/><rect x="${x - 12}" y="400" width="24" height="36" rx="4" fill="#3b2f25"/><rect x="${x - 8}" y="406" width="16" height="24" fill="#ffd98a"/>`;
  return svg(g, s);
}

export function lodge(seed, tone = 'day', o = {}) {
  const r = rng(seed), s = SKIES[tone];
  const horizon = 560;
  let g = sky(r, s, horizon);
  // karlı dağlar
  g += `<path d="M-50 560 L300 180 L420 300 L620 120 L900 420 L1100 220 L1400 460 L1650 300 L1650 600 L-50 600 Z" fill="#7d93ab"/>`;
  g += `<path d="M300 180 L360 250 L330 260 L300 230 L270 262 L240 250 Z M620 120 L700 210 L660 220 L620 190 L590 228 L550 200 Z M1100 220 L1170 300 L1130 300 L1100 280 L1070 306 L1040 290 Z" fill="#f4f8fb"/>`;
  g += ridge(r, 600, 60, '#4c6b58', 1, 60);
  // çam ormanı
  const tree = (x, y, h, c) => {
    let t = `<rect x="${f(x - h * .03)}" y="${f(y - h * .15)}" width="${f(h * .06)}" height="${f(h * .15)}" fill="#4a3424"/>`;
    for (let k = 0; k < 4; k++) t += `<path d="M${f(x - h * (.32 - k * .06))} ${f(y - h * (.12 + k * .2))} L${f(x)} ${f(y - h * (.5 + k * .17))} L${f(x + h * (.32 - k * .06))} ${f(y - h * (.12 + k * .2))} Z" fill="${c}"/>`;
    return t;
  };
  for (let i = 0; i < 40; i++) g += tree(rr(r, 0, W), rr(r, 620, 680), rr(r, 120, 200), pick(r, ['#2d5a3f', '#24503a', '#356a48']));
  g += `<rect y="680" width="${W}" height="${H - 680}" fill="#5f8a4f"/>`;
  // ahşap dağ oteli
  const x = 420, y = 860, w = 760;
  g += shade(x + w / 2, y + 10, w * .6, 22, .3);
  g += `<rect x="${x}" y="${y - 260}" width="${w}" height="260" fill="#8b5e3c"/>`;
  for (let k = 0; k < 13; k++) g += `<line x1="${x}" y1="${y - 260 + k * 20}" x2="${x + w}" y2="${y - 260 + k * 20}" stroke="#6b452b" stroke-width="3"/>`;
  g += `<path d="M${x - 60} ${y - 250} L${x + w / 2} ${y - 470} L${x + w + 60} ${y - 250} Z" fill="#4a3528"/><path d="M${x - 60} ${y - 250} L${x + w / 2} ${y - 470} L${x + w / 2} ${y - 440} L${x - 20} ${y - 250} Z" fill="#fff" opacity=".18"/>`;
  g += `<path d="M${x + w / 2 - 140} ${y - 270} L${x + w / 2} ${y - 400} L${x + w / 2 + 140} ${y - 270} Z" fill="${tone === 'dusk' ? '#ffd58a' : '#9cc6e0'}" opacity=".9"/>`;
  for (let i = 0; i < 6; i++) g += `<rect x="${x + 40 + i * 120}" y="${y - 200}" width="80" height="100" fill="${tone === 'dusk' || r() < .2 ? '#ffd58a' : '#a7cbe0'}" opacity=".9"/><rect x="${x + 36 + i * 120}" y="${y - 100}" width="88" height="10" fill="#5a3a24"/>`;
  g += `<rect x="${x - 20}" y="${y - 70}" width="${w + 40}" height="16" fill="#5a3a24"/>`;
  for (let i = 0; i < 20; i++) g += `<rect x="${x - 10 + i * 40}" y="${y - 60}" width="6" height="60" fill="#5a3a24"/>`;
  for (let i = 0; i < 14; i++) g += tree(rr(r, 0, W), rr(r, 900, 1010), rr(r, 220, 360), pick(r, ['#1f4a33', '#24503a']));
  if (tone === 'dusk') g += `<rect width="${W}" height="${H}" fill="#1b1d4a" opacity=".2"/>`;
  return svg(g, s);
}

export function city(seed, tone = 'dusk', o = {}) {
  const r = rng(seed), s = SKIES[tone];
  const horizon = 760;
  let g = sky(r, s, horizon) + ridge(r, 640, 60, s === SKIES.dusk ? '#4b4470' : '#86a6c2', .8);
  // arka binalar
  for (let i = 0; i < 14; i++) {
    const bw = rr(r, 80, 160), bh = rr(r, 120, 300), bx = rr(r, -40, W);
    g += `<rect x="${f(bx)}" y="${f(horizon - bh)}" width="${f(bw)}" height="${f(bh)}" fill="${s === SKIES.dusk ? '#2e3458' : '#9fb3c7'}" opacity=".9"/>`;
    for (let k = 0; k < 10; k++) g += `<rect x="${f(bx + rr(r, 6, bw - 14))}" y="${f(horizon - bh + rr(r, 8, bh - 14))}" width="8" height="10" fill="#ffd58a" opacity="${tone === 'dusk' ? .8 : 0}"/>`;
  }
  // ana kule (cam)
  const x = 560, w = 480, top = 120;
  g += `<path d="M${x} ${top} L${x + w} ${top + 40} L${x + w} ${horizon} L${x} ${horizon} Z" fill="url(#glass)"/>`;
  g += `<path d="M${x + w} ${top + 40} L${x + w + 140} ${top + 90} L${x + w + 140} ${horizon} L${x + w} ${horizon} Z" fill="#203a5e"/>`;
  for (let fl = 0; fl < 24; fl++) {
    const y = top + 50 + fl * 25;
    g += `<line x1="${x}" y1="${y}" x2="${x + w}" y2="${y + 4}" stroke="#d7ecfa" stroke-opacity=".35"/>`;
    for (let c = 0; c < 10; c++) if (tone === 'dusk' && r() < .45) g += `<rect x="${x + 10 + c * 47}" y="${y + 6}" width="38" height="14" fill="#ffd58a" opacity=".85"/>`;
  }
  for (let c = 0; c <= 10; c++) g += `<line x1="${x + c * 48}" y1="${top + c * 4}" x2="${x + c * 48}" y2="${horizon}" stroke="#d7ecfa" stroke-opacity=".25"/>`;
  g += `<rect x="${x - 40}" y="${horizon - 70}" width="${w + 220}" height="70" fill="#1d2a40"/><rect x="${x + 120}" y="${horizon - 60}" width="240" height="60" fill="#ffcf7a" opacity=".85"/>`;
  // yol, ağaçlar, lambalar
  g += `<rect y="${horizon}" width="${W}" height="${H - horizon}" fill="#2b2f3a"/><rect y="${horizon}" width="${W}" height="40" fill="#8b8f96"/>`;
  for (let i = 0; i < 10; i++) g += `<rect x="${i * 170 + 40}" y="${horizon + 120}" width="90" height="10" fill="#e8e3d6" opacity=".7"/>`;
  for (let i = 0; i < 6; i++) {
    const lx = 80 + i * 300;
    g += `<rect x="${lx}" y="${horizon - 180}" width="6" height="190" fill="#3b3f48"/><circle cx="${lx + 3}" cy="${horizon - 185}" r="40" fill="#ffd58a" opacity="${tone === 'dusk' ? .5 : .15}" filter="url(#blur14)"/><circle cx="${lx + 3}" cy="${horizon - 185}" r="8" fill="#fff1c9"/>`;
    g += palm(r, lx + 150, horizon + 30, 240, rr(r, -.2, .2), tone === 'dusk');
  }
  return svg(g, s);
}

export function meeting(seed, o = {}) {
  const r = rng(seed);
  const accent = o.accent || '#2f6f9f';
  let g = `<rect width="${W}" height="${H}" fill="#ebe6de"/>`;
  g += `<rect x="0" y="0" width="${W}" height="120" fill="#f6f3ee"/>`;
  for (let i = 0; i < 4; i++) g += `<rect x="${200 + i * 320}" y="40" width="240" height="30" rx="15" fill="#fff6dd"/><ellipse cx="${320 + i * 320}" cy="110" rx="200" ry="60" fill="#fff2c9" opacity=".35" filter="url(#blur30)"/>`;
  g += `<rect x="460" y="170" width="680" height="380" rx="8" fill="#20293a"/><rect x="480" y="190" width="640" height="340" fill="${accent}" opacity=".85"/>`;
  g += `<path d="M520 470 L650 380 L760 430 L900 300 L1080 360" fill="none" stroke="#fff" stroke-width="8" opacity=".85"/>`;
  g += `<rect y="650" width="${W}" height="${H - 650}" fill="#7b6d5f"/>`;
  g += `<path d="M300 700 L1300 700 L1520 980 L80 980 Z" fill="#5a4a3c"/><path d="M300 700 L1300 700 L1310 716 L290 716 Z" fill="#fff" opacity=".15"/>`;
  for (let i = 0; i < 6; i++) {
    const t = i / 5, yl = 700 + t * 280;
    const xl = 300 - 220 * t - 70, xr = 1300 + 220 * t + 10, sc = 0.8 + t * 0.8;
    g += `<rect x="${f(xl)}" y="${f(yl - 60 * sc)}" width="${f(60 * sc)}" height="${f(90 * sc)}" rx="${f(10 * sc)}" fill="#2d3442"/>`;
    g += `<rect x="${f(xr)}" y="${f(yl - 60 * sc)}" width="${f(60 * sc)}" height="${f(90 * sc)}" rx="${f(10 * sc)}" fill="#2d3442"/>`;
  }
  return svg(g, SKIES.day);
}
