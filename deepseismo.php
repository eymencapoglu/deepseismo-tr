<?php
// API endpoint URL (set DEEPSEISMO_API_URL environment variable)
$api_url = getenv('DEEPSEISMO_API_URL') ?: 'http://localhost:8000/live';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// KRİTİK: Ngrok uyarı sayfasını atlamak için bu header şart
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'ngrok-skip-browser-warning: true'
));
$response = curl_exec($ch);
curl_close($ch);
$data = json_decode($response, true);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DeepSeismo-TR | Sismik İzleme Sistemi</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Share+Tech+Mono&family=Rajdhani:wght@300;500;700&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:       #020811;
    --surface:  #040f1e;
    --panel:    #071428;
    --border:   #0d2a4a;
    --glow-b:   #00d4ff;
    --glow-r:   #ff2040;
    --glow-g:   #00ff88;
    --glow-y:   #ffcc00;
    --text:     #c8e8ff;
    --muted:    #3a6080;
    --safe:     #00ff88;
    --danger:   #ff2040;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  html { scroll-behavior: smooth; }

  body {
    font-family: 'Rajdhani', sans-serif;
    background: var(--bg);
    color: var(--text);
    overflow-x: hidden;
    cursor: none;
  }

  /* ─── CUSTOM CURSOR ─── */
  #cursor-ring {
    position: fixed; top: 0; left: 0; width: 40px; height: 40px;
    border: 1px solid var(--glow-b); border-radius: 50%;
    pointer-events: none; z-index: 9999;
    transform: translate(-50%, -50%);
    transition: transform 0.08s ease, width 0.2s, height 0.2s, border-color 0.2s;
    mix-blend-mode: screen;
  }
  #cursor-dot {
    position: fixed; top: 0; left: 0; width: 6px; height: 6px;
    background: var(--glow-b); border-radius: 50%;
    pointer-events: none; z-index: 9999;
    transform: translate(-50%, -50%);
    box-shadow: 0 0 8px var(--glow-b);
  }
  #cursor-ring.pulse { width: 60px; height: 60px; border-color: var(--glow-r); border-width: 2px; }

  /* ─── HERO ─── */
  #hero {
    position: relative;
    height: 100vh;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    overflow: hidden;
    background: radial-gradient(ellipse at 50% 60%, #041830 0%, var(--bg) 70%);
  }

  canvas#seismo-canvas {
    position: absolute; inset: 0; width: 100%; height: 100%;
    opacity: 0.6;
  }

  .hero-content {
    position: relative; z-index: 2; text-align: center;
  }

  .hero-label {
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.75rem; letter-spacing: 0.4em;
    color: var(--glow-b); text-transform: uppercase;
    animation: fadeSlideUp 1s 0.2s both;
  }

  .hero-title {
    font-family: 'Orbitron', monospace;
    font-size: clamp(2.5rem, 7vw, 5.5rem);
    font-weight: 900;
    line-height: 1;
    color: #fff;
    text-shadow: 0 0 40px rgba(0,212,255,0.4), 0 0 80px rgba(0,212,255,0.1);
    animation: fadeSlideUp 1s 0.4s both;
  }

  .hero-title span { color: var(--glow-b); }

  .hero-sub {
    margin-top: 1rem;
    font-size: 1.15rem; font-weight: 300; letter-spacing: 0.15em;
    color: #5a9abf;
    animation: fadeSlideUp 1s 0.6s both;
  }

  .hero-stats {
    margin-top: 2.5rem;
    display: flex; gap: 3rem; justify-content: center;
    animation: fadeSlideUp 1s 0.8s both;
  }

  .hstat { text-align: center; }
  .hstat-val {
    font-family: 'Orbitron', monospace; font-size: 1.8rem; font-weight: 700;
    color: var(--glow-b);
    text-shadow: 0 0 20px var(--glow-b);
  }
  .hstat-lbl { font-size: 0.7rem; letter-spacing: 0.2em; color: var(--muted); text-transform: uppercase; }

  .scroll-hint {
    position: absolute; bottom: 2rem; left: 50%; transform: translateX(-50%);
    display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
    font-size: 0.7rem; letter-spacing: 0.3em; color: var(--muted); text-transform: uppercase;
    animation: bounce 2s infinite;
  }
  .scroll-hint::after {
    content: ''; display: block; width: 1px; height: 50px;
    background: linear-gradient(to bottom, var(--glow-b), transparent);
  }

  /* ─── SECTION BASE ─── */
  section {
    padding: 7rem 2rem;
    max-width: 1200px;
    margin: 0 auto;
  }

  .section-tag {
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.7rem; letter-spacing: 0.5em; color: var(--glow-b); text-transform: uppercase;
    margin-bottom: 0.6rem;
  }

  .section-title {
    font-family: 'Orbitron', monospace;
    font-size: clamp(1.6rem, 4vw, 2.8rem);
    font-weight: 700; color: #fff; margin-bottom: 1rem;
  }

  .divider {
    width: 60px; height: 2px;
    background: linear-gradient(to right, var(--glow-b), transparent);
    margin-bottom: 3rem;
  }

  /* ─── VISION / MISSION ─── */
  #vision-mission {
    background: linear-gradient(180deg, var(--bg) 0%, #030d1a 100%);
    max-width: 100%;
    padding: 7rem 2rem;
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
  }

  #vision-mission .inner { max-width: 1200px; margin: 0 auto; }

  .vm-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2px;
    margin-bottom: 4rem;
  }
  @media(max-width: 768px) { .vm-grid { grid-template-columns: 1fr; } }

  .vm-card {
    background: var(--panel);
    border: 1px solid var(--border);
    padding: 3rem;
    position: relative;
    overflow: hidden;
  }
  .vm-card::before {
    content: '';
    position: absolute; top: 0; left: 0;
    width: 100%; height: 3px;
  }
  .vm-card.vision::before { background: linear-gradient(to right, var(--glow-b), transparent); }
  .vm-card.mission::before { background: linear-gradient(to right, var(--glow-r), transparent); }

  .vm-icon {
    font-size: 2.5rem; margin-bottom: 1rem;
    display: block;
  }

  .vm-head {
    font-family: 'Orbitron', monospace;
    font-size: 1.1rem; font-weight: 700;
    letter-spacing: 0.15em; text-transform: uppercase;
    margin-bottom: 1.2rem;
  }
  .vision .vm-head { color: var(--glow-b); }
  .mission .vm-head { color: var(--glow-r); }

  .vm-text { font-size: 1rem; font-weight: 300; line-height: 1.8; color: #8ab4cc; }

  .richter-bar {
    margin-top: 4rem;
    background: var(--panel); border: 1px solid var(--border); padding: 2.5rem;
  }

  .rb-title {
    font-family: 'Orbitron', monospace;
    font-size: 0.85rem; letter-spacing: 0.3em; color: var(--muted); text-transform: uppercase;
    margin-bottom: 2rem;
  }

  .rb-scale { display: flex; gap: 4px; align-items: flex-end; height: 80px; }

  .rb-col {
    flex: 1; position: relative;
    display: flex; flex-direction: column; align-items: center; justify-content: flex-end;
    gap: 6px;
  }

  .rb-bar {
    width: 100%; border-radius: 2px 2px 0 0;
    animation: barRise 1.5s ease both;
  }

  .rb-mag {
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.65rem; color: var(--muted);
  }

  /* ─── TECHNOLOGY ─── */
  #technology {
    background: var(--bg);
  }

  .arch-diagram {
    background: var(--panel);
    border: 1px solid var(--border);
    padding: 2.5rem;
    margin-bottom: 2rem;
    position: relative;
  }

  .arch-layers {
    display: flex; flex-direction: column; gap: 2px;
  }

  .arch-layer {
    display: flex; align-items: center; gap: 1rem;
    padding: 1rem 1.5rem;
    border-left: 3px solid;
    background: rgba(255,255,255,0.02);
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.85rem;
    position: relative;
  }

  .arch-layer:nth-child(1) { border-color: var(--glow-b); }
  .arch-layer:nth-child(2) { border-color: var(--glow-y); }
  .arch-layer:nth-child(3) { border-color: var(--glow-r); }
  .arch-layer:nth-child(4) { border-color: var(--glow-g); }

  .layer-num {
    font-family: 'Orbitron', monospace; font-size: 0.7rem; font-weight: 700;
    opacity: 0.4; min-width: 24px;
  }

  .layer-name { color: #fff; font-weight: 600; min-width: 200px; }
  .layer-desc { color: var(--muted); font-size: 0.78rem; }

  .layer-pill {
    margin-left: auto; font-size: 0.65rem; padding: 2px 10px;
    border-radius: 999px; background: rgba(0,212,255,0.1);
    border: 1px solid rgba(0,212,255,0.3); color: var(--glow-b);
    letter-spacing: 0.1em; text-transform: uppercase;
    white-space: nowrap;
  }

  .arch-arrow {
    display: flex; justify-content: center; padding: 4px;
    font-family: 'Share Tech Mono', monospace; font-size: 0.8rem; color: var(--muted);
  }

  /* API Output */
  .api-section-title {
    font-family: 'Orbitron', monospace; font-size: 0.9rem;
    letter-spacing: 0.25em; color: var(--glow-b); text-transform: uppercase;
    margin: 3rem 0 1.5rem;
    display: flex; align-items: center; gap: 1rem;
  }
  .api-section-title::after {
    content: ''; flex: 1; height: 1px;
    background: linear-gradient(to right, var(--border), transparent);
  }

  /* ─── STATION GRID ─── */
  .station-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1px;
    background: var(--border);
    border: 1px solid var(--border);
  }

  .station-card {
    background: var(--panel);
    padding: 1.8rem 2rem;
    position: relative;
    overflow: hidden;
    transition: background 0.2s;
  }

  .station-card::after {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(ellipse at 50% 0%, rgba(0,212,255,0.04) 0%, transparent 70%);
    pointer-events: none;
  }

  .station-card.DEPREM {
    background: #12040a;
  }
  .station-card.DEPREM::after {
    background: radial-gradient(ellipse at 50% 0%, rgba(255,32,64,0.08) 0%, transparent 70%);
    animation: pulseGlow 1.5s infinite;
  }

  .card-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 1.5rem;
  }

  .station-name {
    font-family: 'Orbitron', monospace;
    font-size: 0.95rem; font-weight: 700; color: #fff;
    letter-spacing: 0.05em;
  }

  .status-badge {
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.65rem; padding: 3px 10px;
    border-radius: 999px; letter-spacing: 0.15em; text-transform: uppercase;
    font-weight: 700;
  }
  .badge-SAKIN {
    background: rgba(0,255,136,0.1); border: 1px solid rgba(0,255,136,0.4);
    color: var(--safe);
  }
  .badge-DEPREM {
    background: rgba(255,32,64,0.2); border: 1px solid rgba(255,32,64,0.6);
    color: var(--danger);
    animation: badgeBlink 0.8s infinite;
  }

  .confidence-row {
    margin-bottom: 1rem;
  }

  .conf-label {
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.7rem; color: var(--muted); letter-spacing: 0.2em;
    text-transform: uppercase; margin-bottom: 0.4rem;
    display: flex; justify-content: space-between;
  }

  .conf-val { color: var(--glow-b); }

  .conf-track {
    height: 3px; background: var(--border); border-radius: 2px; overflow: hidden;
  }

  .conf-fill {
    height: 100%; border-radius: 2px;
    transition: width 1s ease;
  }
  .conf-fill.high { background: linear-gradient(to right, var(--safe), #00cc6e); }
  .conf-fill.mid  { background: linear-gradient(to right, var(--glow-y), #cc9900); }
  .conf-fill.low  { background: linear-gradient(to right, var(--danger), #cc0020); }

  .card-footer {
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.7rem; color: var(--muted); letter-spacing: 0.1em;
    padding-top: 1rem; border-top: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center;
  }

  .pulse-dot {
    width: 6px; height: 6px; border-radius: 50%; background: var(--safe);
    animation: dotPulse 2s infinite;
  }
  .pulse-dot.active { background: var(--danger); animation-duration: 0.5s; }

  /* Empty state */
  .empty-state {
    grid-column: 1 / -1;
    background: var(--panel);
    padding: 4rem; text-align: center;
  }
  .empty-state p {
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.9rem; color: var(--muted); letter-spacing: 0.2em;
  }

  /* ─── FOOTER ─── */
  footer {
    border-top: 1px solid var(--border);
    padding: 3rem 2rem;
    text-align: center;
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.7rem; color: var(--muted); letter-spacing: 0.2em;
    background: var(--surface);
  }

  /* ─── ANIMATIONS ─── */
  @keyframes fadeSlideUp {
    from { opacity: 0; transform: translateY(30px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  @keyframes bounce {
    0%, 100% { transform: translateX(-50%) translateY(0); }
    50%       { transform: translateX(-50%) translateY(-8px); }
  }
  @keyframes barRise {
    from { transform: scaleY(0); transform-origin: bottom; }
    to   { transform: scaleY(1); }
  }
  @keyframes badgeBlink {
    0%, 100% { opacity: 1; } 50% { opacity: 0.3; }
  }
  @keyframes pulseGlow {
    0%, 100% { opacity: 0.3; } 50% { opacity: 1; }
  }
  @keyframes dotPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(0,255,136,0.6); }
    50%       { box-shadow: 0 0 0 6px rgba(0,255,136,0); }
  }
  @keyframes scanline {
    0%   { transform: translateY(-100%); }
    100% { transform: translateY(100vh); }
  }

  /* ─── NAVBAR ─── */
  #navbar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
    height: 60px;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 2.5rem;
    background: rgba(2,8,17,0.7);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(0,212,255,0.1);
    transition: background 0.3s;
  }
  #navbar.scrolled {
    background: rgba(2,8,17,0.95);
    border-bottom-color: var(--border);
  }

  .nav-logo {
    font-family: 'Orbitron', monospace;
    font-size: 0.9rem; font-weight: 900; letter-spacing: 0.15em;
    color: #fff; text-decoration: none;
    display: flex; align-items: center; gap: 0.5rem;
  }
  .nav-logo span { color: var(--glow-b); }
  .nav-logo::before {
    content: '◈';
    color: var(--glow-b);
    font-size: 1rem;
    animation: dotPulse 2s infinite;
  }

  .nav-links {
    display: flex; align-items: center; gap: 2.5rem;
    list-style: none;
  }
  .nav-links a {
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.7rem; letter-spacing: 0.25em; text-transform: uppercase;
    color: var(--muted); text-decoration: none;
    transition: color 0.2s;
    position: relative;
  }
  .nav-links a::after {
    content: '';
    position: absolute; bottom: -4px; left: 0; right: 0;
    height: 1px; background: var(--glow-b);
    transform: scaleX(0); transform-origin: left;
    transition: transform 0.25s ease;
  }
  .nav-links a:hover { color: var(--glow-b); }
  .nav-links a:hover::after { transform: scaleX(1); }

  .nav-right { display: flex; align-items: center; gap: 1.2rem; }

  /* Lang switcher */
  .lang-switch {
    display: flex; align-items: center; gap: 2px;
    background: var(--panel); border: 1px solid var(--border);
    border-radius: 4px; overflow: hidden;
  }
  .lang-btn {
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.65rem; letter-spacing: 0.15em;
    padding: 5px 10px; cursor: pointer;
    background: transparent; border: none; color: var(--muted);
    transition: background 0.2s, color 0.2s;
  }
  .lang-btn.active { background: var(--glow-b); color: #000; font-weight: 700; }
  .lang-btn:hover:not(.active) { color: var(--glow-b); }

  /* Hamburger */
  .hamburger {
    display: none;
    flex-direction: column; justify-content: center; align-items: center;
    width: 40px; height: 40px; gap: 5px;
    cursor: pointer; background: none; border: none;
    position: relative; z-index: 1100;
  }
  .hamburger span {
    display: block; width: 22px; height: 1.5px;
    background: var(--glow-b);
    border-radius: 2px;
    transition: transform 0.35s cubic-bezier(.4,0,.2,1), opacity 0.25s, width 0.3s;
    transform-origin: center;
  }
  .hamburger.open span:nth-child(1) { transform: translateY(6.5px) rotate(45deg); }
  .hamburger.open span:nth-child(2) { opacity: 0; width: 0; }
  .hamburger.open span:nth-child(3) { transform: translateY(-6.5px) rotate(-45deg); }

  /* Mobile overlay */
  #mobile-menu {
    display: none;
    position: fixed; inset: 0; z-index: 1050;
    background: rgba(2,8,17,0.92);
    backdrop-filter: blur(24px);
    flex-direction: column; align-items: center; justify-content: center;
    gap: 2rem;
    opacity: 0;
    transition: opacity 0.35s ease;
  }
  #mobile-menu.open { opacity: 1; }
  #mobile-menu a {
    font-family: 'Orbitron', monospace;
    font-size: 1.5rem; font-weight: 700; letter-spacing: 0.15em;
    color: #fff; text-decoration: none;
    transform: translateY(20px); opacity: 0;
    transition: transform 0.4s ease, opacity 0.4s ease, color 0.2s;
  }
  #mobile-menu.open a { transform: translateY(0); opacity: 1; }
  #mobile-menu.open a:nth-child(1) { transition-delay: 0.05s; }
  #mobile-menu.open a:nth-child(2) { transition-delay: 0.1s; }
  #mobile-menu.open a:nth-child(3) { transition-delay: 0.15s; }
  #mobile-menu.open a:nth-child(4) { transition-delay: 0.2s; }
  #mobile-menu a:hover { color: var(--glow-b); }
  #mobile-menu .mob-lang {
    display: flex; gap: 0.8rem; margin-top: 1rem;
    transition-delay: 0.25s !important;
    transform: translateY(20px); opacity: 0;
    transition: transform 0.4s ease 0.25s, opacity 0.4s ease 0.25s;
  }
  #mobile-menu.open .mob-lang { transform: translateY(0); opacity: 1; }

  /* Mobile scanline decoration */
  #mobile-menu::before {
    content: '';
    position: absolute; inset: 0;
    background: repeating-linear-gradient(
      0deg, transparent, transparent 3px, rgba(0,212,255,0.015) 3px, rgba(0,212,255,0.015) 4px
    );
    pointer-events: none;
  }

  @media(max-width: 768px) {
    .nav-links, .lang-switch { display: none; }
    .hamburger { display: flex; }
    #mobile-menu { display: flex; }
  }

  /* ─── CONTACT ─── */
  #contact {
    background: linear-gradient(180deg, #030d1a 0%, var(--bg) 100%);
    max-width: 100%;
    padding: 7rem 2rem;
    border-top: 1px solid var(--border);
  }
  #contact .inner { max-width: 1200px; margin: 0 auto; }

  .contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2px;
  }
  @media(max-width: 768px) { .contact-grid { grid-template-columns: 1fr; } }

  .contact-card {
    background: var(--panel);
    border: 1px solid var(--border);
    padding: 3rem;
    position: relative;
    overflow: hidden;
    transition: border-color 0.3s, background 0.3s;
  }
  .contact-card::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 2px;
    background: linear-gradient(to right, var(--glow-b), transparent);
  }
  .contact-card:hover {
    border-color: rgba(0,212,255,0.3);
    background: #081827;
  }
  .contact-card::after {
    content: '';
    position: absolute; bottom: 0; right: 0;
    width: 120px; height: 120px;
    background: radial-gradient(circle, rgba(0,212,255,0.05) 0%, transparent 70%);
    pointer-events: none;
  }

  .contact-avatar {
    width: 48px; height: 48px; border-radius: 50%;
    background: linear-gradient(135deg, rgba(0,212,255,0.2), rgba(0,212,255,0.05));
    border: 1px solid rgba(0,212,255,0.3);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Orbitron', monospace; font-size: 1rem; font-weight: 700;
    color: var(--glow-b); margin-bottom: 1.5rem;
    text-shadow: 0 0 10px var(--glow-b);
  }

  .contact-name {
    font-family: 'Orbitron', monospace;
    font-size: 1rem; font-weight: 700; color: #fff;
    letter-spacing: 0.1em; margin-bottom: 0.4rem;
  }

  .contact-role {
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.7rem; letter-spacing: 0.2em; color: var(--muted);
    text-transform: uppercase; margin-bottom: 1.5rem;
  }

  .contact-phone {
    display: flex; align-items: center; gap: 0.8rem;
    font-family: 'Share Tech Mono', monospace;
    font-size: 1rem; color: var(--glow-b);
    text-decoration: none;
    letter-spacing: 0.05em;
    transition: text-shadow 0.2s;
  }
  .contact-phone:hover { text-shadow: 0 0 12px var(--glow-b); }
  .contact-phone::before {
    content: '◈';
    font-size: 0.7rem;
    animation: dotPulse 2s infinite;
  }

  /* Hero padding for fixed navbar */
  #hero { padding-top: 60px; }

  /* Global scanline overlay */
  body::after {
    content: '';
    position: fixed; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(to bottom, transparent, rgba(0,212,255,0.05), transparent);
    pointer-events: none; z-index: 100;
    animation: scanline 8s linear infinite;
  }
</style>
</head>
<body>

<!-- CURSOR -->
<div id="cursor-ring"></div>
<div id="cursor-dot"></div>

<!-- ═══════════════════════════════ NAVBAR ═══════════════════════════════ -->
<nav id="navbar">
  <a href="#hero" class="nav-logo">DEEP<span>SEISMO</span>-TR</a>

  <ul class="nav-links" id="nav-links">
    <li><a href="#hero"            data-tr="Ana Sayfa"  data-en="Home"       >Ana Sayfa</a></li>
    <li><a href="#vision-mission"  data-tr="Vizyon"     data-en="Vision"     >Vizyon</a></li>
    <li><a href="#technology"      data-tr="Teknoloji"  data-en="Technology" >Teknoloji</a></li>
    <li><a href="#contact"         data-tr="İletişim"   data-en="Contact"    >İletişim</a></li>
  </ul>

  <div class="nav-right">
    <div class="lang-switch">
      <button class="lang-btn active" id="btn-tr" onclick="setLang('tr')">TR</button>
      <button class="lang-btn"        id="btn-en" onclick="setLang('en')">EN</button>
    </div>
    <button class="hamburger" id="hamburger" aria-label="Menü" onclick="toggleMenu()">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<!-- Mobile overlay menu -->
<div id="mobile-menu">
  <a href="#hero"           data-tr="Ana Sayfa"  data-en="Home"       onclick="closeMenu()">Ana Sayfa</a>
  <a href="#vision-mission" data-tr="Vizyon"     data-en="Vision"     onclick="closeMenu()">Vizyon</a>
  <a href="#technology"     data-tr="Teknoloji"  data-en="Technology" onclick="closeMenu()">Teknoloji</a>
  <a href="#contact"        data-tr="İletişim"   data-en="Contact"    onclick="closeMenu()">İletişim</a>
  <div class="mob-lang">
    <button class="lang-btn active" id="mob-btn-tr" onclick="setLang('tr')">TR</button>
    <button class="lang-btn"        id="mob-btn-en" onclick="setLang('en')">EN</button>
  </div>
</div>

<!-- ═══════════════════════════════ HERO ═══════════════════════════════ -->
<div id="hero">
  <canvas id="seismo-canvas"></canvas>
  <div class="hero-content">
    <p class="hero-label">⬡ Gerçek Zamanlı Sismik İzleme Sistemi · Türkiye</p>
    <h1 class="hero-title">DEEP<span>SEISMO</span>-TR</h1>
    <p class="hero-sub">Çift Katmanlı Hibrit AI Analiz Mimarisi</p>
    <div class="hero-stats">
      <div class="hstat">
        <div class="hstat-val" id="stat-stations">—</div>
        <div class="hstat-lbl">Aktif İstasyon</div>
      </div>
      <div class="hstat">
        <div class="hstat-val" id="stat-alerts">—</div>
        <div class="hstat-lbl">Aktif Alarm</div>
      </div>
      <div class="hstat">
        <div class="hstat-val">&lt;200ms</div>
        <div class="hstat-lbl">Analiz Gecikmesi</div>
      </div>
    </div>
  </div>
  <div class="scroll-hint">aşağı kaydır</div>
</div>

<!-- ═══════════════════════════ VISION / MISSION ═══════════════════════════ -->
<div id="vision-mission">
  <div class="inner">
    <p class="section-tag">// sistem hedefleri</p>
    <h2 class="section-title">Vizyon & Misyon</h2>
    <div class="divider"></div>

    <div class="vm-grid">
      <div class="vm-card vision">
        <span class="vm-icon">◈</span>
        <div class="vm-head">Vizyonumuz</div>
        <p class="vm-text">
          Türkiye, dünyanın en aktif sismik kuşaklarından biri olan Alp-Himalaya kuşağı üzerinde konumlanmaktadır.
          1999 Marmara depreminin 17.000'den fazla can alması, erken uyarı sistemlerinin tartışmasız önemini ortaya koymuştur.
          DeepSeismo-TR'nin vizyonu; ülke genelinde kurulan sismometre ağından saniyeler içinde anlam üretebilen,
          gürültüyü gerçek sinyalden ayırabilen ve yanlış alarmları minimize eden bir yapay zekâ tabanlı izleme ekosistemine
          öncülük etmektir. Hedef: Yıkım başlamadan <em>önce</em> uyarmak.
        </p>
      </div>
      <div class="vm-card mission">
        <span class="vm-icon">◇</span>
        <div class="vm-head">Misyonumuz</div>
        <p class="vm-text">
          Depremler, yıllık ortalama 50.000 insan ölümüne ve 100 milyar dolar ekonomik kayba yol açmaktadır.
          Bu tahribatın büyük bölümü, yaşanan 10-60 saniyelik P-S dalgası aralığından yeterince yararlanılamamasından kaynaklanır.
          Misyonumuz; sismik ham veriyi derin öğrenme modelleri ve klasik sinyal işleme yöntemlerinin
          sinerjisiyle anında sınıflandırarak afet yönetim birimlerine, ulaşım altyapısına ve kritik tesislere
          saniyenin altında aksiyon verebilecek çıktılar sunmaktır.
        </p>
      </div>
    </div>

    <!-- Richter scale visual -->
    <div class="richter-bar">
      <div class="rb-title">// Richter Ölçeği — Büyüklük & Etki Dağılımı</div>
      <div class="rb-scale" id="richter-scale"></div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════ TECHNOLOGY ═══════════════════════════ -->
<section id="technology">
  <p class="section-tag">// mimari</p>
  <h2 class="section-title">Teknoloji</h2>
  <div class="divider"></div>

  <p style="font-size:1.05rem;font-weight:300;line-height:1.8;color:#8ab4cc;max-width:800px;margin-bottom:2.5rem;">
    Tüm istasyon verileri, <strong style="color:#fff;">Çift Katmanlı Hibrit Analiz Mimarisi</strong> aracılığıyla
    gerçek zamanlı olarak analiz edilmektedir. Birinci katman klasik STA/LTA ve Butterworth bant-geçiren filtrelerle
    ham sinyali ön-işlerken; ikinci katman 1-D evrişimli sinir ağları (CNN) ve LSTM bloklarından oluşan
    derin öğrenme modeli ile anlık sınıflandırma yapar.
  </p>

  <div class="arch-diagram">
    <div class="arch-layers">
      <div class="arch-layer">
        <span class="layer-num">01</span>
        <span class="layer-name">Sismometre Ağı (Ham Veri)</span>
        <span class="layer-desc">3-eksenli MEMS & geniş-bant sensörler · 100 SPS örnekleme</span>
        <span class="layer-pill">input</span>
      </div>
      <div class="arch-arrow">↓  WebSocket akışı  ↓</div>
      <div class="arch-layer">
        <span class="layer-num">02</span>
        <span class="layer-name">Katman 1 — Klasik DSP</span>
        <span class="layer-desc">STA/LTA tetikleyici · Butterworth filtre · Gürültü normalizasyonu</span>
        <span class="layer-pill">sinyal işleme</span>
      </div>
      <div class="arch-arrow">↓  özellik vektörü  ↓</div>
      <div class="arch-layer">
        <span class="layer-num">03</span>
        <span class="layer-name">Katman 2 — Derin Öğrenme (CNN + LSTM)</span>
        <span class="layer-desc">1-D konvolüsyon · Dikkat mekanizması · Softmax çıkış</span>
        <span class="layer-pill">AI sınıflandırma</span>
      </div>
      <div class="arch-arrow">↓  JSON çıktı  ↓</div>
      <div class="arch-layer">
        <span class="layer-num">04</span>
        <span class="layer-name">REST API — /live Endpoint</span>
        <span class="layer-desc">Confidence skoru · Durum etiketi · Zaman damgası · &lt;200ms gecikme</span>
        <span class="layer-pill">çıktı</span>
      </div>
    </div>
  </div>

  <!-- API Output Section -->
  <div class="api-section-title">// API CANLI ÇIKTILARI — /live</div>

  <div class="station-grid">
    <?php if(!empty($data)): foreach($data as $st): ?>
      <?php
        $cleanStatus = str_replace(array('✅ ', '🚨 ', '⚠️ '), '', $st['status']);
        $conf = floatval($st['ai_confidence']);
        $confPct = round($conf * 100, 1);
        $confClass = $conf >= 0.75 ? 'high' : ($conf >= 0.45 ? 'mid' : 'low');
        $isQuake = $cleanStatus === 'DEPREM';
      ?>
      <div class="station-card <?php echo $cleanStatus; ?>">
        <div class="card-top">
          <div class="station-name"><?php echo htmlspecialchars($st['station_name']); ?></div>
          <span class="status-badge badge-<?php echo $cleanStatus; ?>">
            <?php echo $isQuake ? '⚠ DEPREM' : '✓ SAKIN'; ?>
          </span>
        </div>

        <div class="confidence-row">
          <div class="conf-label">
            <span>AI Güven Skoru</span>
            <span class="conf-val">%<?php echo $confPct; ?></span>
          </div>
          <div class="conf-track">
            <div class="conf-fill <?php echo $confClass; ?>" style="width:<?php echo $confPct; ?>%"></div>
          </div>
        </div>

        <div class="card-footer">
          <span><?php echo htmlspecialchars($st['last_update']); ?></span>
          <div class="pulse-dot <?php echo $isQuake ? 'active' : ''; ?>"></div>
        </div>
      </div>
    <?php endforeach; else: ?>
      <div class="empty-state">
        <p>◈ VERİ BEKLENİYOR — Python motoru ve API'nin açık olduğundan emin olun</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ═══════════════════════════ CONTACT ═══════════════════════════ -->
<div id="contact">
  <div class="inner">
    <p class="section-tag" data-tr="// iletişim" data-en="// contact">// iletişim</p>
    <h2 class="section-title" data-tr="İletişim" data-en="Contact">İletişim</h2>
    <div class="divider"></div>

    <p style="font-size:1.05rem;font-weight:300;line-height:1.8;color:#8ab4cc;max-width:700px;margin-bottom:3rem;"
       data-tr="Projelerimiz hakkında detaylı bilgi almak veya işbirliği için bizimle iletişime geçin."
       data-en="Contact us for detailed information about our projects or for collaboration.">
      Projelerimiz hakkında detaylı bilgi almak veya işbirliği için bizimle iletişime geçin.
    </p>

    <div class="contact-grid">
      <div class="contact-card">
        <div class="contact-avatar">AK</div>
        <div class="contact-name">Abdullah Kaan Akdağ</div>
        <div class="contact-role" data-tr="Kurucu & Geliştirici" data-en="Co-Founder & Developer">Kurucu & Geliştirici</div>
        <a href="tel:+905510609962" class="contact-phone">+90 551 060 9962</a>
      </div>
      <div class="contact-card">
        <div class="contact-avatar">EÇ</div>
        <div class="contact-name">Eymen Çapoğlu</div>
        <div class="contact-role" data-tr="Kurucu & Geliştirici" data-en="Co-Founder & Developer">Kurucu & Geliştirici</div>
        <a href="tel:+905384013081" class="contact-phone">+90 538 401 30 81</a>
      </div>
    </div>
  </div>
</div>

<footer>
  <p>DEEPSEISMO-TR · ÇİFT KATMANLI HİBRİT ANALİZ MİMARİSİ · GERÇEK ZAMANLI SİSMİK İZLEME</p>
  <p style="margin-top:0.5rem;opacity:0.4;">© <?php echo date('Y'); ?> · TÜRKİYE ULUSAL SİSMİK AĞINA BAĞLI</p>
</footer>

<script>
/* ─── CURSOR ─── */
const ring = document.getElementById('cursor-ring');
const dot  = document.getElementById('cursor-dot');
let mx = 0, my = 0, rx = 0, ry = 0;
document.addEventListener('mousemove', e => { mx = e.clientX; my = e.clientY; });
;(function animCursor() {
  rx += (mx - rx) * 0.12;
  ry += (my - ry) * 0.12;
  ring.style.left = rx + 'px';
  ring.style.top  = ry + 'px';
  dot.style.left  = mx + 'px';
  dot.style.top   = my + 'px';
  requestAnimationFrame(animCursor);
})();

document.addEventListener('mousedown', () => ring.classList.add('pulse'));
document.addEventListener('mouseup',   () => ring.classList.remove('pulse'));

/* ─── HERO SEISMIC CANVAS ─── */
const canvas = document.getElementById('seismo-canvas');
const ctx = canvas.getContext('2d');
let W, H;
const lines = [];
const LINE_COUNT = 12;

function resize() {
  W = canvas.width  = canvas.offsetWidth;
  H = canvas.height = canvas.offsetHeight;
}
resize();
window.addEventListener('resize', resize);

function createLine(index) {
  const y = (H / (LINE_COUNT + 1)) * (index + 1);
  return { y, baseY: y, points: [], speed: 1 + Math.random() * 0.8, amp: 0, phase: Math.random() * Math.PI * 2 };
}

for (let i = 0; i < LINE_COUNT; i++) lines.push(createLine(i));

// mouse influence
let mxN = 0.5, myN = 0.5;
document.addEventListener('mousemove', e => {
  mxN = e.clientX / window.innerWidth;
  myN = e.clientY / window.innerHeight;
});

let t = 0;
function drawSeismo() {
  ctx.clearRect(0, 0, W, H);
  t += 0.012;

  lines.forEach((line, idx) => {
    const proximity = 1 - Math.abs(myN - (idx + 1) / (LINE_COUNT + 1)) * 3;
    const mouseAmp = Math.max(0, proximity) * 60 * mxN;
    const baseAmp = 4 + Math.sin(t * 0.3 + idx) * 3;
    const totalAmp = baseAmp + mouseAmp;

    ctx.beginPath();
    for (let x = 0; x <= W; x += 3) {
      const nx = x / W;
      const freq = 3 + idx * 0.7;
      const envelope = Math.sin(nx * Math.PI) * totalAmp;
      const wave =
        Math.sin(nx * freq * 6.28 - t * line.speed + line.phase) * envelope +
        Math.sin(nx * freq * 12.56 - t * line.speed * 1.3) * envelope * 0.3 +
        (Math.random() - 0.5) * baseAmp * 0.4;

      const py = line.y + wave;
      x === 0 ? ctx.moveTo(x, py) : ctx.lineTo(x, py);
    }

    const alpha = 0.12 + Math.max(0, proximity) * 0.3 * mxN;
    const isHot = proximity > 0 && mxN > 0.5;
    ctx.strokeStyle = isHot
      ? `rgba(255, 32, 64, ${alpha})`
      : `rgba(0, 212, 255, ${alpha * 0.7})`;
    ctx.lineWidth = 1;
    ctx.stroke();
  });

  requestAnimationFrame(drawSeismo);
}
drawSeismo();

/* ─── HERO STATS from PHP data ─── */
<?php if(!empty($data)): ?>
const stationCount = <?php echo count($data); ?>;
const alertCount = <?php echo count(array_filter($data, fn($s) => str_replace(['✅ ','🚨 ','⚠️ '], '', $s['status']) === 'DEPREM')); ?>;
document.getElementById('stat-stations').textContent = stationCount;
document.getElementById('stat-alerts').textContent = alertCount === 0 ? '0' : alertCount;
<?php else: ?>
document.getElementById('stat-stations').textContent = '0';
document.getElementById('stat-alerts').textContent = '—';
<?php endif; ?>

/* ─── NAVBAR SCROLL ─── */
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
  navbar.classList.toggle('scrolled', window.scrollY > 40);
});

/* ─── MOBILE MENU ─── */
let menuOpen = false;
const mobileMenu = document.getElementById('mobile-menu');
const hamburger  = document.getElementById('hamburger');

function toggleMenu() {
  menuOpen = !menuOpen;
  hamburger.classList.toggle('open', menuOpen);
  if (menuOpen) {
    mobileMenu.style.display = 'flex';
    requestAnimationFrame(() => mobileMenu.classList.add('open'));
    document.body.style.overflow = 'hidden';
  } else {
    closeMenu();
  }
}

function closeMenu() {
  menuOpen = false;
  hamburger.classList.remove('open');
  mobileMenu.classList.remove('open');
  document.body.style.overflow = '';
  setTimeout(() => { if (!menuOpen) mobileMenu.style.display = 'none'; }, 350);
}

/* ─── LANGUAGE SWITCHER ─── */
let currentLang = 'tr';

function setLang(lang) {
  currentLang = lang;

  // Buttons active state
  ['tr','en'].forEach(l => {
    document.getElementById('btn-'    + l)?.classList.toggle('active', l === lang);
    document.getElementById('mob-btn-' + l)?.classList.toggle('active', l === lang);
  });

  // Translate all elements with data-tr / data-en
  document.querySelectorAll('[data-tr][data-en]').forEach(el => {
    el.textContent = el.getAttribute('data-' + lang);
  });

  // Dynamic: vision/mission headings & texts (these are in PHP-rendered HTML, updated via data attrs on wrappers)
  // Hero
  document.querySelector('.hero-sub').textContent =
    lang === 'tr' ? 'Çift Katmanlı Hibrit Analiz Mimarisi' : 'Dual-Layer Hybrid Analysis Architecture';
  document.querySelector('.hero-label').textContent =
    lang === 'tr' ? '⬡ Gerçek Zamanlı Sismik İzleme Sistemi · Türkiye' : '⬡ Real-Time Seismic Monitoring System · Turkey';

  // Richter title
  const rbTitle = document.querySelector('.rb-title');
  if (rbTitle) rbTitle.textContent = lang === 'tr'
    ? '// Richter Ölçeği — Büyüklük & Etki Dağılımı'
    : '// Richter Scale — Magnitude & Impact Distribution';

  // Section tags & titles (use data attrs where possible, update hardcoded ones)
  const sectionMappings = [
    ['#vision-mission .section-tag',   '// sistem hedefleri',    '// system objectives'],
    ['#vision-mission .section-title', 'Vizyon & Misyon',        'Vision & Mission'],
    ['#technology .section-tag',       '// mimari',              '// architecture'],
    ['#technology .section-title',     'Teknoloji',              'Technology'],
    ['#technology p:first-of-type',    null, null], // handled below
  ];

  sectionMappings.forEach(([sel, tr, en]) => {
    const el = document.querySelector(sel);
    if (el && tr) el.textContent = lang === 'tr' ? tr : en;
  });

  // Technology intro paragraph
  const techIntro = document.querySelector('#technology > p');
  if (techIntro) techIntro.innerHTML = lang === 'tr'
    ? 'Tüm istasyon verileri, <strong style="color:#fff;">Çift Katmanlı Hibrit Analiz Mimarisi</strong> aracılığıyla gerçek zamanlı olarak analiz edilmektedir. Birinci katman klasik STA/LTA ve Butterworth bant-geçiren filtrelerle ham sinyali ön-işlerken; ikinci katman 1-D evrişimli sinir ağları (CNN) ve LSTM bloklarından oluşan derin öğrenme modeli ile anlık sınıflandırma yapar.'
    : 'All station data is analyzed in real-time through the <strong style="color:#fff;">Dual-Layer Hybrid Analysis Architecture</strong>. The first layer pre-processes raw signals using classical STA/LTA and Butterworth band-pass filters; the second layer performs instant classification using deep learning models composed of 1-D convolutional neural networks (CNN) and LSTM blocks.';

  // API output title
  const apiTitle = document.querySelector('.api-section-title');
  if (apiTitle) apiTitle.textContent = lang === 'tr'
    ? '// API CANLI ÇIKTILARI — /live'
    : '// LIVE API OUTPUT — /live';

  // Footer
  const footer = document.querySelector('footer p:first-child');
  if (footer) footer.textContent = lang === 'tr'
    ? 'DEEPSEISMO-TR · ÇİFT KATMANLI HİBRİT ANALİZ MİMARİSİ · GERÇEK ZAMANLI SİSMİK İZLEME'
    : 'DEEPSEISMO-TR · DUAL-LAYER HYBRID ANALYSIS ARCHITECTURE · REAL-TIME SEISMIC MONITORING';

  // Arch layer names
  const layerNames = document.querySelectorAll('.layer-name');
  const layerDescs = document.querySelectorAll('.layer-desc');
  const names_tr = [
    'Sismometre Ağı (Ham Veri)',
    'Katman 1 — Klasik DSP',
    'Katman 2 — Derin Öğrenme (CNN + LSTM)',
    'REST API — /live Endpoint'
  ];
  const names_en = [
    'Seismometer Network (Raw Data)',
    'Layer 1 — Classical DSP',
    'Layer 2 — Deep Learning (CNN + LSTM)',
    'REST API — /live Endpoint'
  ];
  const descs_tr = [
    '3-eksenli MEMS & geniş-bant sensörler · 100 SPS örnekleme',
    'STA/LTA tetikleyici · Butterworth filtre · Gürültü normalizasyonu',
    '1-D konvolüsyon · Dikkat mekanizması · Softmax çıkış',
    'Confidence skoru · Durum etiketi · Zaman damgası · <200ms gecikme'
  ];
  const descs_en = [
    '3-axis MEMS & broadband sensors · 100 SPS sampling',
    'STA/LTA trigger · Butterworth filter · Noise normalization',
    '1-D convolution · Attention mechanism · Softmax output',
    'Confidence score · Status label · Timestamp · <200ms latency'
  ];
  layerNames.forEach((el, i) => { if (lang === 'tr') el.textContent = names_tr[i]; else el.textContent = names_en[i]; });
  layerDescs.forEach((el, i) => { if (lang === 'tr') el.textContent = descs_tr[i]; else el.textContent = descs_en[i]; });

  // Arch arrows
  document.querySelectorAll('.arch-arrow').forEach((el, i) => {
    const arr_tr = ['↓  WebSocket akışı  ↓','↓  özellik vektörü  ↓','↓  JSON çıktı  ↓'];
    const arr_en = ['↓  WebSocket stream  ↓','↓  feature vector  ↓','↓  JSON output  ↓'];
    el.textContent = lang === 'tr' ? arr_tr[i] : arr_en[i];
  });

  // Vision / mission cards text
  const vmTexts = document.querySelectorAll('.vm-text');
  const vmHeads = document.querySelectorAll('.vm-head');
  if (vmHeads[0]) vmHeads[0].textContent = lang === 'tr' ? 'Vizyonumuz' : 'Our Vision';
  if (vmHeads[1]) vmHeads[1].textContent = lang === 'tr' ? 'Misyonumuz' : 'Our Mission';
  if (vmTexts[0]) vmTexts[0].textContent = lang === 'tr'
    ? 'Türkiye, dünyanın en aktif sismik kuşaklarından biri olan Alp-Himalaya kuşağı üzerinde konumlanmaktadır. 1999 Marmara depreminin 17.000\'den fazla can alması, erken uyarı sistemlerinin tartışmasız önemini ortaya koymuştur. DeepSeismo-TR\'nin vizyonu; ülke genelinde kurulan sismometre ağından saniyeler içinde anlam üretebilen, gürültüyü gerçek sinyalden ayırabilen ve yanlış alarmları minimize eden bir yapay zekâ tabanlı izleme ekosistemine öncülük etmektir. Hedef: Yıkım başlamadan önce uyarmak.'
    : 'Turkey sits on the Alp-Himalayan belt, one of the world\'s most seismically active zones. The 1999 Marmara earthquake claimed more than 17,000 lives, demonstrating the undeniable importance of early warning systems. DeepSeismo-TR\'s vision is to pioneer an AI-driven monitoring ecosystem capable of extracting meaning from a nationwide seismometer network within seconds, distinguishing noise from true signals, and minimizing false alarms. The goal: warn before destruction begins.';
  if (vmTexts[1]) vmTexts[1].textContent = lang === 'tr'
    ? 'Depremler, yıllık ortalama 50.000 insan ölümüne ve 100 milyar dolar ekonomik kayba yol açmaktadır. Bu tahribatın büyük bölümü, yaşanan 10-60 saniyelik P-S dalgası aralığından yeterince yararlanılamamasından kaynaklanır. Misyonumuz; sismik ham veriyi derin öğrenme modelleri ve klasik sinyal işleme yöntemlerinin sinerjisiyle anında sınıflandırarak afet yönetim birimlerine, ulaşım altyapısına ve kritik tesislere saniyenin altında aksiyon verebilecek çıktılar sunmaktır.'
    : 'Earthquakes cause an average of 50,000 deaths and $100 billion in economic losses annually. Much of this damage stems from insufficient use of the 10–60 second P-S wave window. Our mission is to instantly classify seismic raw data through the synergy of deep learning models and classical signal processing methods, delivering sub-second actionable outputs to disaster management units, transportation infrastructure, and critical facilities.';

  // Empty state
  const emptyP = document.querySelector('.empty-state p');
  if (emptyP) emptyP.textContent = lang === 'tr'
    ? '◈ VERİ BEKLENİYOR — Python motoru ve API\'nin açık olduğundan emin olun'
    : '◈ AWAITING DATA — Ensure Python engine and API are running';

  // Station card labels
  document.querySelectorAll('.conf-label span:first-child').forEach(el => {
    el.textContent = lang === 'tr' ? 'AI Güven Skoru' : 'AI Confidence';
  });
  document.querySelectorAll('.status-badge').forEach(el => {
    if (el.classList.contains('badge-SAKIN'))  el.textContent = lang === 'tr' ? '✓ SAKIN'  : '✓ CALM';
    if (el.classList.contains('badge-DEPREM')) el.textContent = lang === 'tr' ? '⚠ DEPREM' : '⚠ QUAKE';
  });

  // hstat labels
  const hlbls = document.querySelectorAll('.hstat-lbl');
  const hlbls_tr = ['Aktif İstasyon','Aktif Alarm','Analiz Gecikmesi'];
  const hlbls_en = ['Active Stations','Active Alerts','Analysis Latency'];
  hlbls.forEach((el, i) => { el.textContent = lang === 'tr' ? hlbls_tr[i] : hlbls_en[i]; });

  // scroll hint
  const sh = document.querySelector('.scroll-hint');
  if (sh) sh.childNodes[0].textContent = lang === 'tr' ? 'aşağı kaydır' : 'scroll down';
}

const richterData = [
  { mag: '1-2', color: '#00ff88', h: 10,  label: 'Mikro' },
  { mag: '2-3', color: '#44ff88', h: 18,  label: 'Çok Küçük' },
  { mag: '3-4', color: '#88ff44', h: 28,  label: 'Küçük' },
  { mag: '4-5', color: '#ccff00', h: 40,  label: 'Orta' },
  { mag: '5-6', color: '#ffcc00', h: 55,  label: 'Orta-Güçlü' },
  { mag: '6-7', color: '#ff8800', h: 66,  label: 'Güçlü' },
  { mag: '7-8', color: '#ff4400', h: 78,  label: 'Büyük' },
  { mag: '8+',  color: '#ff2040', h: 90,  label: 'Büyük' },
];

const rbScale = document.getElementById('richter-scale');
richterData.forEach((d, i) => {
  const col = document.createElement('div');
  col.className = 'rb-col';
  col.innerHTML = `
    <div class="rb-bar" style="height:${d.h}%;background:${d.color};animation-delay:${i*0.1}s;"></div>
    <div class="rb-mag" style="color:${d.color}">${d.mag}</div>
  `;
  col.title = d.label;
  rbScale.appendChild(col);
});
</script>
</body>
</html>
