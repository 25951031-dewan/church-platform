<style>
/* ===== THEME VARIABLES =====
   To change theme colors, edit the CSS variables below.
   Dark theme: :root (default)
   Light theme: [data-theme="light"]
*/
:root {
  --bg-primary: #0C0E12;
  --bg-secondary: #14171E;
  --bg-card: #1A1E28;
  --bg-card-hover: #1F2433;
  --bg-elevated: #242936;
  --gold: #C9A84C;
  --gold-light: #E8D48B;
  --gold-dark: #A07C28;
  --gold-glow: rgba(201, 168, 76, 0.15);
  --cream: #F5F0E8;
  --cream-dim: #B8B0A0;
  --text-primary: #E8E4DC;
  --text-secondary: #9A9488;
  --text-muted: #6B6560;
  --accent-blue: #4A7C9B;
  --accent-rose: #9B4A6A;
  --accent-green: #4A9B6A;
  --border: rgba(201, 168, 76, 0.12);
  --border-strong: rgba(201, 168, 76, 0.25);
  --shadow-sm: 0 2px 8px rgba(0,0,0,0.3);
  --shadow-md: 0 4px 20px rgba(0,0,0,0.4);
  --shadow-lg: 0 8px 40px rgba(0,0,0,0.5);
  --shadow-gold: 0 0 30px rgba(201, 168, 76, 0.1);
  --nav-bg: rgba(12, 14, 18, 0.9);
  --mobile-bg: rgba(12, 14, 18, 0.97);
  --radius-sm: 8px;
  --radius-md: 12px;
  --radius-lg: 16px;
  --radius-xl: 24px;
  --font-display: 'Playfair Display', Georgia, serif;
  --font-body: 'Source Sans 3', 'Segoe UI', sans-serif;
  --font-elegant: 'Cormorant Garamond', Georgia, serif;
}
/* ===== LIGHT THEME ===== */
[data-theme="light"] {
  --bg-primary: #F8F6F2;
  --bg-secondary: #EFECE6;
  --bg-card: #FFFFFF;
  --bg-card-hover: #F5F2EC;
  --bg-elevated: #E8E4DC;
  --gold: #9B7D2E;
  --gold-light: #7A6324;
  --gold-dark: #6B5520;
  --gold-glow: rgba(155, 125, 46, 0.12);
  --cream: #1A1510;
  --cream-dim: #5A5040;
  --text-primary: #2C261E;
  --text-secondary: #6B6358;
  --text-muted: #9A9488;
  --border: rgba(155, 125, 46, 0.15);
  --border-strong: rgba(155, 125, 46, 0.30);
  --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
  --shadow-md: 0 4px 20px rgba(0,0,0,0.1);
  --shadow-lg: 0 8px 40px rgba(0,0,0,0.12);
  --shadow-gold: 0 0 30px rgba(155, 125, 46, 0.08);
  --nav-bg: rgba(248, 246, 242, 0.92);
  --mobile-bg: rgba(248, 246, 242, 0.98);
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  background: var(--bg-primary); color: var(--text-primary);
  font-family: var(--font-body); line-height: 1.6;
  -webkit-font-smoothing: antialiased; min-height: 100vh;
  transition: background 0.3s, color 0.3s;
}
body::before {
  content: ''; position: fixed; inset: 0; pointer-events: none; z-index: 0;
  background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,0.06) 0%, transparent 60%),
    radial-gradient(ellipse 60% 40% at 80% 100%, rgba(74,124,155,0.04) 0%, transparent 50%);
}
[data-theme="light"] body::before { opacity: 0.3; }
/* NAV */
.nav-bar {
  position: sticky; top: 0; z-index: 100;
  background: var(--nav-bg); backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border); padding: 0 1.5rem;
}
.nav-inner {
  max-width: 1300px; margin: 0 auto;
  display: flex; align-items: center; justify-content: space-between; height: 60px;
}
.nav-brand {
  display: flex; align-items: center; gap: 10px;
  text-decoration: none; color: var(--gold); cursor: pointer; flex-shrink: 0;
}
.nav-brand-icon {
  width: 36px; height: 36px; border-radius: 50%;
  background: linear-gradient(135deg, var(--gold-dark), var(--gold));
  display: flex; align-items: center; justify-content: center;
  color: var(--bg-primary); font-size: 1.1rem; box-shadow: var(--shadow-gold);
}
.nav-brand-text { font-family: var(--font-display); font-size: 1.05rem; font-weight: 700; }
.nav-links { display: flex; gap: 2px; list-style: none; }
.nav-link {
  padding: 6px 12px; border-radius: var(--radius-sm);
  font-size: 0.82rem; font-weight: 500; color: var(--text-secondary);
  cursor: pointer; transition: all 0.2s; border: none; background: none;
  font-family: var(--font-body); white-space: nowrap;
}
.nav-link:hover { color: var(--text-primary); background: rgba(201,168,76,0.08); }
.nav-link.active { color: var(--gold); background: var(--gold-glow); }
/* Nav Dropdown */
.nav-dropdown-wrap { position: relative; }
.nav-dropdown-arrow { font-size: 0.6rem; margin-left: 2px; }
.nav-dropdown {
  display: none; position: absolute; top: 100%; left: 0; min-width: 180px; z-index: 50;
  background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-md);
  box-shadow: var(--shadow-md); padding: 4px 0; margin-top: 4px;
}
.nav-dropdown-wrap:hover .nav-dropdown { display: block; }
.nav-dropdown-item {
  display: block; width: 100%; text-align: left; padding: 8px 14px;
  font-size: 0.82rem; font-weight: 500; color: var(--text-secondary);
  background: none; border: none; cursor: pointer; font-family: var(--font-body); transition: all 0.2s;
}
.nav-dropdown-item:hover { color: var(--gold); background: var(--gold-glow); }
.mobile-nav-sub { padding-left: 2.5rem; font-size: 0.85rem; opacity: 0.8; }
.nav-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.nav-mobile-btn {
  display: none; background: none; border: none;
  color: var(--text-primary); cursor: pointer; padding: 6px; font-size: 1.4rem; line-height: 1;
}
.theme-toggle {
  background: none; border: 1px solid var(--border); border-radius: 50%;
  width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: var(--gold); font-size: 1rem; transition: all 0.2s;
}
.theme-toggle:hover { background: var(--gold-glow); border-color: var(--border-strong); }
/* MOBILE MENU */
.mobile-menu {
  position: fixed; inset: 0; z-index: 200;
  background: var(--mobile-bg); backdrop-filter: blur(20px);
  display: flex; flex-direction: column;
  transform: translateX(100%); transition: transform 0.3s ease;
  overflow-y: auto;
}
.mobile-menu.open { transform: translateX(0); }
.mobile-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px; border-bottom: 1px solid var(--border); flex-shrink: 0;
}
.mobile-header-brand { font-family: var(--font-display); font-size: 1.1rem; font-weight: 700; color: var(--gold); }
.mobile-close {
  background: none; border: 1px solid var(--border); border-radius: 50%;
  width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
  color: var(--text-primary); cursor: pointer; font-size: 1.2rem; transition: all 0.2s;
}
.mobile-close:hover { background: var(--gold-glow); border-color: var(--border-strong); }
.mobile-nav-list { display: flex; flex-direction: column; padding: 12px 0; flex: 1; }
.mobile-nav-item {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 24px; font-size: 1rem; font-weight: 500;
  color: var(--text-secondary); cursor: pointer; transition: all 0.2s;
  border: none; background: none; font-family: var(--font-body); text-align: left; width: 100%;
  border-bottom: 1px solid var(--border);
}
.mobile-nav-item:last-child { border-bottom: none; }
.mobile-nav-item:hover { color: var(--text-primary); background: rgba(201,168,76,0.06); }
.mobile-nav-item.active { color: var(--gold); background: var(--gold-glow); }
.mobile-nav-icon { font-size: 1.1rem; width: 24px; text-align: center; }
@media (max-width: 900px) { .nav-links { display: none; } .nav-mobile-btn { display: block; } }
/* MAIN */
.main-content { position: relative; z-index: 1; max-width: 1300px; margin: 0 auto; padding: 1.5rem; }
.page-section { display: none; }
.page-section.active { display: block; }
/* HERO */
.hero-section { text-align: center; padding: 3rem 1.5rem; position: relative; }
.hero-section::before {
  content: '\2726'; position: absolute; top: 0; left: 50%;
  transform: translateX(-50%); font-size: 1rem; color: var(--gold); opacity: 0.5;
}
.hero-label {
  font-family: var(--font-body); font-size: 0.72rem; font-weight: 600;
  letter-spacing: 0.2em; text-transform: uppercase; color: var(--gold);
  margin-bottom: 1.2rem; display: flex; align-items: center; justify-content: center; gap: 12px;
}
.hero-label::before, .hero-label::after { content: ''; width: 40px; height: 1px; background: var(--gold); opacity: 0.3; }
.verse-text {
  font-family: var(--font-elegant); font-size: clamp(1.4rem, 3.2vw, 2.3rem);
  font-weight: 400; font-style: italic; line-height: 1.5;
  color: var(--cream); max-width: 780px; margin: 0 auto 1.2rem; text-wrap: balance;
}
.verse-ref { font-family: var(--font-display); font-size: 0.95rem; font-weight: 600; color: var(--gold); letter-spacing: 0.05em; }
/* BLESSING */
.blessing-card {
  background: linear-gradient(135deg, var(--bg-card), var(--bg-elevated));
  border: 1px solid var(--border); border-radius: var(--radius-xl);
  padding: 2.5rem; text-align: center; margin: 1.5rem 0;
  position: relative; overflow: hidden; box-shadow: var(--shadow-gold); transition: all 0.3s;
}
.blessing-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, transparent, var(--gold), transparent); }
.blessing-title { font-family: var(--font-display); font-size: 1.4rem; font-weight: 700; color: var(--gold-light); margin-bottom: 0.8rem; }
.blessing-text { font-family: var(--font-elegant); font-size: 1.15rem; color: var(--text-primary); max-width: 680px; margin: 0 auto; line-height: 1.8; }
.blessing-author { margin-top: 0.8rem; font-size: 0.85rem; color: var(--text-secondary); }
/* SECTIONS */
.section-header {
  display: flex; align-items: center; justify-content: space-between;
  margin: 2.5rem 0 1.2rem; padding-bottom: 0.8rem; border-bottom: 1px solid var(--border);
  flex-wrap: wrap; gap: 10px;
}
.section-title { font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; color: var(--cream); display: flex; align-items: center; gap: 8px; }
.section-action {
  padding: 7px 18px; background: var(--gold-glow); border: 1px solid var(--border-strong);
  border-radius: var(--radius-sm); color: var(--gold); font-family: var(--font-body);
  font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.section-action:hover { background: var(--gold); color: var(--bg-primary); }
/* CARDS */
.cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.2rem; }
.card {
  background: var(--bg-card); border: 1px solid var(--border);
  border-radius: var(--radius-lg); padding: 1.4rem; transition: all 0.3s;
}
.card:hover { background: var(--bg-card-hover); border-color: var(--border-strong); transform: translateY(-2px); box-shadow: var(--shadow-md); }
.card-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.68rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.6rem; }
.badge-worship { background: rgba(201,168,76,0.15); color: var(--gold); }
.badge-study { background: rgba(74,124,155,0.15); color: var(--accent-blue); }
.badge-outreach { background: rgba(74,155,106,0.15); color: var(--accent-green); }
.badge-fellowship { background: rgba(155,74,106,0.15); color: var(--accent-rose); }
.badge-other { background: rgba(150,150,150,0.15); color: #999; }
.card-title { font-family: var(--font-display); font-size: 1.1rem; font-weight: 600; color: var(--cream); margin-bottom: 0.4rem; }
.card-meta { font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 0.3rem; }
.card-desc { font-size: 0.88rem; color: var(--text-muted); line-height: 1.6; }
/* PRAYER */
.prayer-name { font-family: var(--font-display); font-size: 0.95rem; font-weight: 600; color: var(--gold-light); margin-bottom: 0.4rem; }
.prayer-text { font-size: 0.92rem; color: var(--text-primary); line-height: 1.6; margin-bottom: 0.8rem; }
.prayer-footer { display: flex; align-items: center; justify-content: space-between; }
.prayer-date { font-size: 0.78rem; color: var(--text-muted); }
.pray-btn {
  display: flex; align-items: center; gap: 5px; padding: 5px 12px;
  border-radius: var(--radius-sm); background: var(--gold-glow);
  border: 1px solid var(--border-strong); color: var(--gold);
  font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: var(--font-body);
}
.pray-btn:hover, .pray-btn.prayed { background: var(--gold); color: var(--bg-primary); }
/* BOOKS */
.book-card {
  background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg);
  padding: 1.4rem; display: flex; gap: 1rem; transition: all 0.3s;
}
.book-card:hover { border-color: var(--border-strong); transform: translateY(-2px); }
.book-cover { font-size: 2.8rem; flex-shrink: 0; }
.book-info { flex: 1; }
.book-title { font-family: var(--font-display); font-size: 1rem; font-weight: 600; color: var(--cream); margin-bottom: 2px; }
.book-author { font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 0.4rem; }
.book-category { display: inline-block; padding: 2px 9px; border-radius: 12px; font-size: 0.68rem; font-weight: 600; background: rgba(74,124,155,0.15); color: var(--accent-blue); margin-bottom: 0.4rem; }
.book-actions { display: flex; gap: 6px; margin-top: 0.4rem; }
.book-btn {
  padding: 4px 12px; border-radius: var(--radius-sm); font-size: 0.78rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: var(--font-body);
  border: 1px solid var(--border); background: transparent; color: var(--text-secondary);
}
.book-btn:hover { border-color: var(--border-strong); color: var(--gold); }
.book-btn-pdf { background: var(--gold-glow); border-color: var(--border-strong); color: var(--gold); }
.book-btn-pdf:hover { background: var(--gold); color: var(--bg-primary); }
.study-meta { display: flex; gap: 1rem; font-size: 0.82rem; color: var(--text-secondary); flex-wrap: wrap; }
/* SERMONS */
.sermon-card {
  background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg);
  padding: 1.4rem; display: flex; align-items: flex-start; gap: 1rem; transition: all 0.3s;
}
.sermon-card:hover { border-color: var(--border-strong); }
.sermon-play {
  width: 46px; height: 46px; border-radius: 50%; background: var(--gold-glow);
  border: 1px solid var(--border-strong); color: var(--gold);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; cursor: pointer; transition: all 0.2s; font-size: 1rem;
}
.sermon-play:hover { background: var(--gold); color: var(--bg-primary); }
/* REVIEWS */
.review-stars { color: var(--gold); margin-bottom: 0.6rem; letter-spacing: 2px; }
.review-text { font-size: 0.92rem; color: var(--text-primary); line-height: 1.6; margin-bottom: 0.6rem; font-style: italic; }
.review-author { font-size: 0.82rem; color: var(--text-secondary); }
.avg-rating { font-family: var(--font-display); font-size: 2.8rem; font-weight: 800; color: var(--gold); }
.star-input button { background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 1.3rem; padding: 2px; transition: color 0.1s; }
.star-input button.filled { color: var(--gold); }
.star-input button:hover { color: var(--gold-light); }
/* INFO / ABOUT */
.info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 1.2rem; }
.info-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.4rem; transition: all 0.3s; }
.info-card-title { font-family: var(--font-display); font-size: 0.95rem; font-weight: 600; color: var(--gold-light); margin-bottom: 0.6rem; }
.info-card-text { font-size: 0.88rem; color: var(--text-secondary); line-height: 1.7; }
.social-links { display: flex; gap: 8px; margin-top: 0.8rem; flex-wrap: wrap; }
.social-link {
  padding: 7px 14px; border-radius: var(--radius-sm); background: var(--bg-elevated);
  border: 1px solid var(--border); color: var(--text-secondary); font-size: 0.82rem;
  font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none;
}
.social-link:hover { color: var(--gold); border-color: var(--border-strong); }
/* GIVING */
.giving-card {
  background: linear-gradient(135deg, var(--bg-card), rgba(201,168,76,0.05));
  border: 1px solid var(--border-strong); border-radius: var(--radius-xl); padding: 2.5rem; text-align: center; transition: all 0.3s;
}
.giving-amounts { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; margin: 1.2rem 0; }
.giving-amount {
  padding: 9px 22px; border-radius: var(--radius-sm); background: var(--bg-elevated);
  border: 1px solid var(--border); color: var(--text-primary); font-size: 0.95rem;
  font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: var(--font-body);
}
.giving-amount:hover, .giving-amount.selected { background: var(--gold-glow); border-color: var(--gold); color: var(--gold); }
/* MINISTRIES */
.volunteer-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; }
.volunteer-card {
  background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg);
  padding: 1.4rem; text-align: center; cursor: pointer; transition: all 0.3s;
}
.volunteer-card:hover { border-color: var(--gold); transform: translateY(-2px); }
.volunteer-icon { font-size: 2.2rem; margin-bottom: 0.5rem; }
.volunteer-name { font-family: var(--font-display); font-size: 0.95rem; font-weight: 600; color: var(--cream); margin-bottom: 0.2rem; }
.volunteer-spots { font-size: 0.78rem; color: var(--text-muted); }
/* TICKER */
.ticker {
  background: var(--gold-glow); border: 1px solid var(--border); border-radius: var(--radius-md);
  padding: 10px 18px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 12px; overflow: hidden;
}
.ticker-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em; color: var(--gold); white-space: nowrap; padding: 3px 9px; border-radius: 4px; background: rgba(201,168,76,0.15); flex-shrink: 0; }
.ticker-text { font-size: 0.85rem; color: var(--text-primary); white-space: nowrap; animation: ticker-scroll 30s linear infinite; }
@keyframes ticker-scroll { from { transform: translateX(100%); } to { transform: translateX(-100%); } }
/* MODAL */
.modal-overlay {
  position: fixed; inset: 0; z-index: 300; background: rgba(0,0,0,0.7);
  display: none; align-items: center; justify-content: center; padding: 1.5rem; backdrop-filter: blur(4px);
}
.modal-overlay.open { display: flex; }
.modal-content {
  background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-xl);
  padding: 2rem; max-width: 560px; width: 100%; max-height: 85vh; overflow-y: auto; box-shadow: var(--shadow-lg);
}
.modal-title { font-family: var(--font-display); font-size: 1.3rem; font-weight: 700; color: var(--cream); margin-bottom: 1.2rem; display: flex; align-items: center; justify-content: space-between; }
.modal-close { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.3rem; }
/* FORMS */
.form-group { margin-bottom: 1rem; }
.form-label { display: block; font-size: 0.78rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 5px; }
.form-input, .form-textarea {
  width: 100%; background: var(--bg-primary); border: 1px solid var(--border); border-radius: var(--radius-sm);
  padding: 10px 14px; color: var(--text-primary); font-family: var(--font-body); font-size: 0.92rem; outline: none; transition: border-color 0.2s;
}
.form-input:focus, .form-textarea:focus { border-color: var(--gold); }
.form-textarea { resize: vertical; min-height: 90px; }
.form-checkbox-row { display: flex; align-items: center; gap: 8px; margin-bottom: 1rem; }
.form-checkbox { accent-color: var(--gold); width: 16px; height: 16px; }
/* BUTTONS */
.btn-primary {
  background: linear-gradient(135deg, var(--gold-dark), var(--gold)); color: #fff;
  border: none; padding: 11px 26px; border-radius: var(--radius-sm); font-family: var(--font-body);
  font-size: 0.92rem; font-weight: 700; cursor: pointer; transition: all 0.2s; width: 100%;
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: var(--shadow-gold); }
.btn-secondary { background: var(--bg-elevated); color: var(--text-primary); border: 1px solid var(--border); padding: 8px 18px; border-radius: var(--radius-sm); font-family: var(--font-body); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-secondary:hover { border-color: var(--border-strong); }
/* SEARCH / FILTER */
.search-bar { display: flex; align-items: center; gap: 10px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 9px 14px; margin-bottom: 1.2rem; }
.search-bar:focus-within { border-color: var(--gold); }
.search-bar input { flex: 1; background: none; border: none; outline: none; color: var(--text-primary); font-family: var(--font-body); font-size: 0.92rem; }
.search-bar input::placeholder { color: var(--text-muted); }
.filter-btns { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 1.2rem; }
.filter-btn { padding: 5px 14px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; background: var(--bg-elevated); border: 1px solid var(--border); color: var(--text-secondary); cursor: pointer; transition: all 0.2s; }
.filter-btn:hover { color: var(--text-primary); border-color: var(--border-strong); }
.filter-btn.active { background: var(--gold-glow); color: var(--gold); border-color: var(--gold); }
/* TOAST */
.toast {
  position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 400; background: var(--bg-elevated);
  border: 1px solid var(--border-strong); border-radius: var(--radius-md); padding: 12px 22px;
  color: var(--gold-light); font-weight: 600; box-shadow: var(--shadow-lg); animation: toast-in 0.3s ease-out; display: none;
}
.toast.show { display: block; }
@keyframes toast-in { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
/* FOOTER */
.site-footer { margin-top: 3rem; padding: 2.5rem 1.5rem; border-top: 1px solid var(--border); text-align: center; }
.footer-brand { font-family: var(--font-display); font-size: 1.1rem; font-weight: 700; color: var(--gold); margin-bottom: 0.4rem; }
.footer-text { font-size: 0.82rem; color: var(--text-muted); }
/* LOADING */
.loading { text-align: center; padding: 3rem; color: var(--text-muted); }
.loading::after { content: ''; display: inline-block; width: 20px; height: 20px; border: 2px solid var(--gold); border-top-color: transparent; border-radius: 50%; animation: spin 0.8s linear infinite; margin-left: 8px; vertical-align: middle; }
@keyframes spin { to { transform: rotate(360deg); } }
/* PWA install banner */
.pwa-install {
  display: none; position: fixed; bottom: 1.5rem; left: 1.5rem; z-index: 400;
  background: var(--bg-card); border: 1px solid var(--border-strong); border-radius: var(--radius-lg);
  padding: 14px 20px; box-shadow: var(--shadow-lg); max-width: 320px;
}
.pwa-install.show { display: flex; align-items: center; gap: 12px; }
.pwa-install-text { flex: 1; }
.pwa-install-title { font-family: var(--font-display); font-size: 0.92rem; font-weight: 600; color: var(--cream); }
.pwa-install-desc { font-size: 0.78rem; color: var(--text-muted); }
.pwa-install-btn {
  padding: 6px 16px; background: var(--gold); color: var(--bg-primary); border: none;
  border-radius: var(--radius-sm); font-family: var(--font-body); font-size: 0.82rem;
  font-weight: 700; cursor: pointer; white-space: nowrap;
}
.pwa-install-close { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; }
/* AUTH NAV */
.auth-nav { display: flex; align-items: center; }
.auth-nav-btn {
  background: none; border: 1px solid var(--border); border-radius: 50%;
  width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: var(--text-secondary); transition: all 0.2s;
}
.auth-nav-btn:hover { color: var(--gold); border-color: var(--border-strong); background: var(--gold-glow); }
.auth-user-menu { position: relative; }
.auth-avatar-btn {
  width: 34px; height: 34px; border-radius: 50%; border: 2px solid var(--gold);
  background: linear-gradient(135deg, var(--gold-dark), var(--gold));
  color: var(--bg-primary); font-weight: 700; font-size: 0.82rem; font-family: var(--font-body);
  cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;
}
.auth-avatar-btn:hover { box-shadow: 0 0 12px rgba(201,168,76,0.4); }
.auth-dropdown {
  position: absolute; top: calc(100% + 8px); right: 0; z-index: 150;
  background: var(--bg-card); border: 1px solid var(--border-strong); border-radius: var(--radius-md);
  min-width: 220px; box-shadow: var(--shadow-lg); display: none;
  overflow: hidden;
}
.auth-dropdown.open { display: block; }
.auth-dropdown-header { padding: 14px 16px; }
.auth-dropdown-name { font-family: var(--font-display); font-weight: 600; font-size: 0.92rem; color: var(--cream); }
.auth-dropdown-email { font-size: 0.78rem; color: var(--text-muted); margin-top: 2px; }
.auth-dropdown-divider { height: 1px; background: var(--border); }
.auth-dropdown-item {
  display: block; width: 100%; padding: 11px 16px; background: none; border: none;
  color: var(--text-secondary); font-family: var(--font-body); font-size: 0.85rem;
  cursor: pointer; text-align: left; transition: all 0.2s;
}
.auth-dropdown-item:hover { background: var(--gold-glow); color: var(--gold); }
/* AUTH MODAL */
.auth-modal { max-width: 440px; }
.auth-error {
  background: rgba(220,50,50,0.1); border: 1px solid rgba(220,50,50,0.3);
  border-radius: var(--radius-sm); padding: 10px 14px; margin-bottom: 1rem;
  font-size: 0.85rem; color: #e85454;
}
.auth-divider {
  display: flex; align-items: center; gap: 12px; margin: 1.2rem 0;
  color: var(--text-muted); font-size: 0.78rem;
}
.auth-divider::before, .auth-divider::after {
  content: ''; flex: 1; height: 1px; background: var(--border);
}
.auth-social-btns { display: flex; gap: 10px; margin-bottom: 1.2rem; }
.auth-social-btn {
  flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;
  padding: 10px 16px; border-radius: var(--radius-sm); font-family: var(--font-body);
  font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
  text-decoration: none; border: 1px solid var(--border);
}
.auth-google { background: var(--bg-primary); color: var(--text-primary); }
.auth-google:hover { border-color: var(--border-strong); background: var(--bg-elevated); }
.auth-facebook { background: var(--bg-primary); color: var(--text-primary); }
.auth-facebook:hover { border-color: var(--border-strong); background: var(--bg-elevated); }
.auth-switch {
  text-align: center; font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;
}
.auth-switch button {
  background: none; border: none; color: var(--gold); font-family: var(--font-body);
  font-size: 0.85rem; font-weight: 600; cursor: pointer; text-decoration: underline;
}
.auth-switch button:hover { color: var(--gold-light); }
/* PDF VIEWER */
.pdf-viewer-overlay {
  position: fixed; inset: 0; z-index: 500; background: rgba(0,0,0,0.92);
  display: none; flex-direction: column; backdrop-filter: blur(6px);
}
.pdf-viewer-overlay.open { display: flex; }
.pdf-viewer-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 20px; background: var(--bg-secondary); border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}
.pdf-viewer-title {
  font-family: var(--font-display); font-size: 1.1rem; font-weight: 600;
  color: var(--cream); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; margin-right: 1rem;
}
.pdf-viewer-controls {
  display: flex; align-items: center; gap: 8px;
}
.pdf-viewer-btn {
  background: var(--bg-elevated); border: 1px solid var(--border); color: var(--text-secondary);
  padding: 6px 14px; border-radius: var(--radius-sm); font-family: var(--font-body);
  font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
  display: flex; align-items: center; gap: 4px;
}
.pdf-viewer-btn:hover { border-color: var(--border-strong); color: var(--gold); }
.pdf-viewer-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.pdf-viewer-btn.close-btn {
  background: rgba(220,50,50,0.1); border-color: rgba(220,50,50,0.3); color: #e85454;
}
.pdf-viewer-btn.close-btn:hover { background: rgba(220,50,50,0.2); }
.pdf-viewer-page-info {
  font-size: 0.85rem; color: var(--text-secondary); font-weight: 600; min-width: 80px; text-align: center;
}
.pdf-viewer-body {
  flex: 1; display: flex; align-items: center; justify-content: center;
  overflow: hidden; position: relative; perspective: 1200px;
}
.pdf-viewer-page-wrap {
  position: relative; display: flex; align-items: center; justify-content: center;
  width: 100%; height: 100%; padding: 20px;
}
.pdf-viewer-canvas-container {
  position: relative; box-shadow: 0 8px 40px rgba(0,0,0,0.6);
  border-radius: 4px; overflow: hidden; transition: transform 0.5s ease; transform-style: preserve-3d;
  max-width: 100%; max-height: 100%;
}
.pdf-viewer-canvas-container.flip-left {
  animation: flipLeft 0.5s ease;
}
.pdf-viewer-canvas-container.flip-right {
  animation: flipRight 0.5s ease;
}
@keyframes flipLeft {
  0% { transform: rotateY(0deg); }
  50% { transform: rotateY(-15deg) scale(0.95); }
  100% { transform: rotateY(0deg); }
}
@keyframes flipRight {
  0% { transform: rotateY(0deg); }
  50% { transform: rotateY(15deg) scale(0.95); }
  100% { transform: rotateY(0deg); }
}
.pdf-viewer-canvas-container canvas {
  display: block; max-width: 100%; max-height: calc(100vh - 120px); width: auto; height: auto;
}
.pdf-viewer-nav-arrow {
  position: absolute; top: 50%; transform: translateY(-50%); z-index: 10;
  background: var(--bg-card); border: 1px solid var(--border-strong); color: var(--gold);
  width: 48px; height: 48px; border-radius: 50%; font-size: 1.4rem;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: all 0.2s; box-shadow: var(--shadow-md);
}
.pdf-viewer-nav-arrow:hover { background: var(--gold); color: var(--bg-primary); }
.pdf-viewer-nav-arrow:disabled { opacity: 0.3; cursor: not-allowed; }
.pdf-viewer-nav-arrow.prev { left: 16px; }
.pdf-viewer-nav-arrow.next { right: 16px; }
.pdf-viewer-loading {
  position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
  background: rgba(0,0,0,0.5); color: var(--gold); font-size: 1rem; font-weight: 600;
}
/* TESTIMONY */
.testimony-card { position: relative; overflow: hidden; }
.testimony-card:hover { border-color: var(--gold); }
.testimony-card .card-desc { font-family: var(--font-elegant); font-size: 0.95rem; line-height: 1.7; }
/* CONTACT PAGE */
.contact-layout { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 1.5rem; }
.contact-form-card {
  background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-xl);
  padding: 2rem; transition: all 0.3s;
}
.contact-info-card { display: flex; flex-direction: column; gap: 1.2rem; }
.contact-info-item {
  background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg);
  padding: 1.2rem 1.4rem; display: flex; align-items: flex-start; gap: 1rem; transition: all 0.3s;
}
.contact-info-item:hover { border-color: var(--border-strong); transform: translateY(-2px); }
.contact-info-icon { font-size: 1.5rem; flex-shrink: 0; margin-top: 2px; }
/* NEWSLETTER POPUP */
.newsletter-popup {
  position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%) translateY(120%);
  z-index: 350; max-width: 420px; width: calc(100% - 2rem);
  background: var(--bg-secondary); border: 1px solid var(--border-strong); border-radius: var(--radius-xl);
  padding: 2rem; box-shadow: var(--shadow-lg); text-align: center;
  transition: transform 0.4s ease, opacity 0.4s ease; opacity: 0;
}
.newsletter-popup.show { transform: translateX(-50%) translateY(0); opacity: 1; }
.newsletter-popup-close {
  position: absolute; top: 12px; right: 12px; background: none; border: none;
  color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px;
}
.newsletter-popup-close:hover { color: var(--text-primary); }
.newsletter-popup-icon { font-size: 2.2rem; margin-bottom: 0.6rem; }
/* NEWSLETTER FOOTER */
.newsletter-footer {
  background: linear-gradient(135deg, var(--bg-card), rgba(201,168,76,0.05));
  border: 1px solid var(--border); border-radius: var(--radius-xl);
  padding: 1.5rem 2rem; margin: 2rem 1.5rem; max-width: 1300px;
}
.newsletter-footer-inner { display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap; }
.newsletter-footer-form { display: flex; gap: 0.6rem; flex: 1; max-width: 450px; }
/* ===== BLOG ===== */
.blog-layout-toggle { display: flex; gap: 4px; }
.blog-layout-btn {
  background: var(--bg-elevated); border: 1px solid var(--border); border-radius: var(--radius-sm);
  color: var(--text-muted); width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: 1rem; transition: all 0.2s; font-family: var(--font-body);
}
.blog-layout-btn:hover { color: var(--text-primary); border-color: var(--border-strong); }
.blog-layout-btn.active { background: var(--gold-glow); color: var(--gold); border-color: var(--gold); }
.blog-search-row { display: flex; gap: 10px; margin-bottom: 1.2rem; }
.blog-main-layout { display: grid; grid-template-columns: 1fr 280px; gap: 2rem; }
.blog-content-area { min-width: 0; }
/* Blog Sidebar */
.blog-sidebar {
  display: flex; flex-direction: column; gap: 1.5rem;
}
.sidebar-widget {
  background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg);
  padding: 1.2rem; transition: all 0.3s;
}
.sidebar-widget:hover { border-color: var(--border-strong); }
.sidebar-title {
  font-family: var(--font-display); font-size: 1rem; font-weight: 700; color: var(--cream);
  margin-bottom: 0.8rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border);
}
.sidebar-cat-item {
  display: flex; align-items: center; justify-content: space-between;
  padding: 8px 12px; border-radius: var(--radius-sm); cursor: pointer;
  font-size: 0.88rem; color: var(--text-secondary); transition: all 0.2s;
}
.sidebar-cat-item:hover { background: var(--gold-glow); color: var(--text-primary); }
.sidebar-cat-item.active { background: var(--gold-glow); color: var(--gold); font-weight: 600; }
.sidebar-recent-item {
  display: flex; align-items: center; gap: 10px; padding: 8px 0;
  border-bottom: 1px solid var(--border); cursor: pointer; transition: all 0.2s;
}
.sidebar-recent-item:last-child { border-bottom: none; }
.sidebar-recent-item:hover { opacity: 0.8; }
.sidebar-recent-img {
  width: 50px; height: 50px; border-radius: var(--radius-sm); object-fit: cover; flex-shrink: 0;
}
.sidebar-recent-placeholder {
  width: 50px; height: 50px; border-radius: var(--radius-sm); background: var(--bg-elevated);
  display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;
}
.sidebar-recent-title { font-size: 0.85rem; font-weight: 600; color: var(--cream); line-height: 1.3; }
.sidebar-recent-date { font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; }
.sidebar-tag {
  display: inline-block; padding: 4px 10px; margin: 3px; border-radius: 20px; font-size: 0.75rem;
  font-weight: 600; background: var(--bg-elevated); border: 1px solid var(--border);
  color: var(--text-secondary); cursor: pointer; transition: all 0.2s;
}
.sidebar-tag:hover { background: var(--gold-glow); color: var(--gold); border-color: var(--gold); }
/* Blog Cards */
.blog-card { cursor: pointer; padding: 0; overflow: hidden; }
.blog-card-img {
  width: 100%; height: 200px; overflow: hidden; position: relative;
}
.blog-card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
.blog-card:hover .blog-card-img img { transform: scale(1.05); }
.blog-card-img-placeholder {
  width: 100%; height: 200px; background: var(--bg-elevated); display: flex;
  align-items: center; justify-content: center; font-size: 3rem; color: var(--text-muted);
}
.blog-card-body { padding: 1.2rem; }
.blog-card-title { margin-bottom: 0.4rem; }
.blog-card-footer {
  display: flex; align-items: center; justify-content: space-between;
  margin-top: 0.6rem; padding-top: 0.6rem; border-top: 1px solid var(--border);
}
.blog-read-more { font-size: 0.82rem; font-weight: 600; color: var(--gold); transition: all 0.2s; }
.blog-card:hover .blog-read-more { color: var(--gold-light); }
.blog-card-tags { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 0.6rem; }
.blog-tag {
  display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.68rem;
  font-weight: 600; background: rgba(74,124,155,0.12); color: var(--accent-blue);
}
/* Blog List Layout */
.blog-list-container { display: flex; flex-direction: column; gap: 1rem; }
.blog-list-item {
  display: flex; gap: 1.2rem; background: var(--bg-card); border: 1px solid var(--border);
  border-radius: var(--radius-lg); overflow: hidden; cursor: pointer; transition: all 0.3s;
}
.blog-list-item:hover { border-color: var(--border-strong); transform: translateY(-2px); box-shadow: var(--shadow-md); }
.blog-list-img {
  width: 200px; min-width: 200px; height: 160px; overflow: hidden; flex-shrink: 0;
}
.blog-list-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
.blog-list-item:hover .blog-list-img img { transform: scale(1.05); }
.blog-list-body { flex: 1; padding: 1.2rem; display: flex; flex-direction: column; }
/* Blog Pagination */
.blog-pagination { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 2rem; }
.blog-page-btn {
  padding: 8px 14px; background: var(--bg-elevated); border: 1px solid var(--border);
  border-radius: var(--radius-sm); color: var(--text-secondary); font-family: var(--font-body);
  font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.blog-page-btn:hover { border-color: var(--border-strong); color: var(--text-primary); }
.blog-page-active { background: var(--gold-glow); color: var(--gold); border-color: var(--gold); pointer-events: none; }
.blog-page-dots { color: var(--text-muted); font-size: 0.85rem; padding: 0 4px; }
.blog-empty { text-align: center; padding: 3rem; color: var(--text-muted); font-style: italic; }
/* Blog Detail */
.blog-detail-container { display: grid; grid-template-columns: 1fr 280px; gap: 2rem; }
.blog-back-btn {
  background: var(--bg-elevated); border: 1px solid var(--border); border-radius: var(--radius-sm);
  padding: 8px 18px; color: var(--text-secondary); font-family: var(--font-body); font-size: 0.85rem;
  font-weight: 600; cursor: pointer; transition: all 0.2s; margin-bottom: 1.5rem; grid-column: 1 / -1;
}
.blog-back-btn:hover { color: var(--gold); border-color: var(--border-strong); }
.blog-article {
  background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-xl);
  overflow: hidden; transition: all 0.3s;
}
.blog-detail-hero { width: 100%; max-height: 420px; overflow: hidden; }
.blog-detail-hero img { width: 100%; height: 100%; object-fit: cover; }
.blog-detail-content { padding: 2rem; }
.blog-detail-title {
  font-family: var(--font-display); font-size: 1.8rem; font-weight: 700; color: var(--cream);
  margin: 0.6rem 0 0.8rem; line-height: 1.3;
}
.blog-detail-meta {
  display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: var(--text-secondary);
  margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);
}
.blog-detail-body {
  font-size: 1rem; color: var(--text-primary); line-height: 1.8;
}
.blog-detail-body h1, .blog-detail-body h2, .blog-detail-body h3 {
  font-family: var(--font-display); color: var(--cream); margin: 1.5rem 0 0.8rem;
}
.blog-detail-body p { margin-bottom: 1rem; }
.blog-detail-body img { max-width: 100%; border-radius: var(--radius-md); margin: 1rem 0; }
.blog-detail-body a { color: var(--gold); text-decoration: underline; }
.blog-detail-body blockquote {
  border-left: 3px solid var(--gold); padding: 0.8rem 1.2rem; margin: 1rem 0;
  background: var(--gold-glow); border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
  font-style: italic; color: var(--cream);
}
.blog-detail-body ul, .blog-detail-body ol { padding-left: 1.5rem; margin-bottom: 1rem; }
.blog-detail-body li { margin-bottom: 0.4rem; }
.blog-detail-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border); }
.blog-share-btn {
  margin-left: auto; background: var(--gold-glow); border: 1px solid var(--border-strong);
  border-radius: var(--radius-sm); padding: 4px 12px; color: var(--gold); font-family: var(--font-body);
  font-size: 0.78rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.blog-share-btn:hover { background: var(--gold); color: var(--bg-primary); }
/* Blog Featured Layout (Homepage Widget) */
.blog-featured-layout { display: grid; grid-template-columns: 1.5fr 1fr; gap: 1.2rem; }
.blog-featured-main {
  position: relative; border-radius: var(--radius-xl); overflow: hidden; cursor: pointer;
  min-height: 300px; background: var(--bg-card); border: 1px solid var(--border);
}
.blog-featured-main:hover { border-color: var(--border-strong); }
.blog-featured-img { width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0; }
.blog-featured-overlay {
  position: absolute; bottom: 0; left: 0; right: 0;
  padding: 1.5rem; background: linear-gradient(transparent, rgba(0,0,0,0.85));
}
.blog-featured-title { font-family: var(--font-display); font-size: 1.3rem; font-weight: 700; color: #fff; margin: 0.4rem 0; }
.blog-featured-excerpt { font-size: 0.88rem; color: rgba(255,255,255,0.8); line-height: 1.5; }
.blog-featured-aside { display: flex; flex-direction: column; gap: 0.8rem; }
.blog-featured-small {
  display: flex; gap: 12px; align-items: center; background: var(--bg-card);
  border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 0.8rem;
  cursor: pointer; transition: all 0.3s;
}
.blog-featured-small:hover { border-color: var(--border-strong); transform: translateY(-2px); }
.blog-featured-small img { width: 80px; height: 80px; min-width: 80px; border-radius: var(--radius-sm); object-fit: cover; }
.blog-featured-small h4 { font-family: var(--font-display); font-size: 0.92rem; font-weight: 600; color: var(--cream); margin-bottom: 4px; }
.blog-detail-sidebar { position: sticky; top: 80px; }
/* ===== MOBILE BOTTOM NAV ===== */
.mobile-bottom-nav {
  display: none; position: fixed; bottom: 0; left: 0; right: 0; z-index: 100;
  background: var(--nav-bg); backdrop-filter: blur(20px);
  border-top: 1px solid var(--border); padding: 4px 0 env(safe-area-inset-bottom, 4px);
  justify-content: space-around; align-items: center;
}
.bottom-nav-item {
  display: flex; flex-direction: column; align-items: center; gap: 2px;
  background: none; border: none; color: var(--text-muted); cursor: pointer;
  padding: 6px 12px; font-family: var(--font-body); transition: all 0.2s; flex: 1;
}
.bottom-nav-item.active { color: var(--gold); }
.bottom-nav-item:hover { color: var(--text-primary); }
.bottom-nav-icon { font-size: 1.1rem; }
.bottom-nav-label { font-size: 0.65rem; font-weight: 600; }
/* QUICK ACTIONS BAR */
.quick-actions-bar {
  display: none; gap: 8px; padding: 0.8rem 0; margin-bottom: 1rem;
  overflow-x: auto; -webkit-overflow-scrolling: touch;
}
.quick-action-btn {
  display: flex; align-items: center; gap: 6px; white-space: nowrap;
  padding: 8px 16px; background: var(--gold-glow); border: 1px solid var(--border-strong);
  border-radius: 20px; color: var(--gold); font-family: var(--font-body);
  font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s; flex-shrink: 0;
}
.quick-action-btn:hover { background: var(--gold); color: var(--bg-primary); }
.quick-action-btn i { font-size: 0.85rem; }
@media (max-width: 900px) {
  .mobile-bottom-nav { display: flex; }
  .quick-actions-bar { display: flex; }
}
/* RESPONSIVE */
@media (max-width: 600px) {
  .main-content { padding: 0.8rem; }
  .hero-section { padding: 2rem 0.5rem; }
  .blessing-card, .giving-card { padding: 1.8rem 1.2rem; }
  .cards-grid { grid-template-columns: 1fr; }
  .info-grid { grid-template-columns: 1fr; }
  .sermon-card { flex-direction: column; }
  .nav-brand-text { font-size: 0.92rem; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .volunteer-grid { grid-template-columns: repeat(2, 1fr); }
  .contact-layout { grid-template-columns: 1fr; }
  .newsletter-footer-inner { flex-direction: column; text-align: center; }
  .newsletter-footer-form { flex-direction: column; max-width: 100%; }
  .blog-main-layout { grid-template-columns: 1fr; }
  .blog-sidebar { order: -1; }
  .blog-detail-container { grid-template-columns: 1fr; }
  .blog-list-item { flex-direction: column; }
  .blog-list-img { width: 100%; min-width: 100%; height: 180px; }
  .blog-featured-layout { grid-template-columns: 1fr; }
  .blog-featured-main { min-height: 220px; }
  .blog-detail-title { font-size: 1.4rem; }
  .blog-detail-content { padding: 1.2rem; }
}

/* ===== CHURCH DIRECTORY & DETAIL ===== */
.church-card { overflow: hidden; padding: 0; transition: transform 0.2s, box-shadow 0.2s; }
.church-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
.church-card-cover { height: 140px; background-size: cover; background-position: center; }
.church-card-cover-default { display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.5); }
.church-card-body { padding: 1rem; position: relative; }
.church-card-logo { width: 48px; height: 48px; border-radius: 10px; object-fit: contain; position: absolute; top: -24px; right: 1rem; background: var(--bg-card); border: 2px solid var(--border); padding: 4px; }
.church-card-name { font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.3rem; }
.church-card-denom { font-size: 0.8rem; color: var(--gold); margin-bottom: 0.3rem; }
.church-card-loc { font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem; }
.church-card-desc { font-size: 0.82rem; color: var(--text-muted); line-height: 1.5; }

/* Church detail page */
.church-page-cover { width: 100%; height: 280px; background-size: cover; background-position: center; border-radius: 16px; position: relative; overflow: hidden; margin-bottom: -60px; }
.church-page-cover-default { display: flex; align-items: center; justify-content: center; font-size: 4rem; color: rgba(255,255,255,0.2); }
.church-page-cover-overlay { position: absolute; inset: 0; }
.church-page-header { display: flex; align-items: center; gap: 1.2rem; padding: 0 1rem; position: relative; z-index: 2; margin-bottom: 2rem; }
.church-page-logo { width: 80px; height: 80px; border-radius: 16px; object-fit: contain; background: var(--bg-card); border: 3px solid var(--border-strong); padding: 6px; }
.church-page-name { font-size: 1.8rem; font-weight: 800; margin-bottom: 0.2rem; }
.church-page-denom { font-size: 0.9rem; color: var(--gold); }
.church-page-loc { font-size: 0.9rem; color: var(--text-secondary); margin-top: 0.2rem; }
.church-page-grid { display: grid; grid-template-columns: 1fr 340px; gap: 2rem; }
.church-page-main { min-width: 0; }
.church-page-sidebar { display: flex; flex-direction: column; gap: 1.2rem; }
.church-page-section { background: var(--bg-card); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.2rem; border: 1px solid var(--border); }
.church-page-section h3 { font-size: 1.15rem; font-weight: 700; margin-bottom: 0.8rem; }
.church-page-section p { color: var(--text-secondary); line-height: 1.7; }
.church-page-history { color: var(--text-secondary); line-height: 1.8; }
.church-page-history p { margin-bottom: 0.8rem; }
.church-page-docs { display: flex; flex-direction: column; gap: 0.5rem; }
.church-page-doc { display: flex; align-items: center; gap: 0.5rem; color: var(--gold); text-decoration: none; padding: 0.6rem 0.8rem; background: var(--bg-secondary); border-radius: 8px; transition: background 0.2s; }
.church-page-doc:hover { background: var(--bg-elevated); }
.church-sidebar-card { background: var(--bg-card); border-radius: 12px; padding: 1.2rem; border: 1px solid var(--border); }
.church-sidebar-card h4 { font-size: 1rem; font-weight: 700; margin-bottom: 0.8rem; }
.church-service-row { display: flex; justify-content: space-between; padding: 0.4rem 0; color: var(--text-primary); font-size: 0.9rem; border-bottom: 1px solid var(--border); }
.church-service-label { font-size: 0.8rem; color: var(--text-muted); padding-bottom: 0.3rem; }
.church-contact-row { font-size: 0.9rem; color: var(--text-secondary); padding: 0.3rem 0; }
.church-contact-row a { color: var(--gold); text-decoration: none; }
.church-contact-row a:hover { text-decoration: underline; }
.church-social-links { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.church-social-link { display: inline-block; padding: 0.4rem 0.9rem; border-radius: 8px; color: #fff; font-size: 0.8rem; font-weight: 600; text-decoration: none; opacity: 0.9; transition: opacity 0.2s; }
.church-social-link:hover { opacity: 1; }

@media(max-width:900px) {
  .church-page-cover { height: 180px; border-radius: 12px; }
  .church-page-grid { grid-template-columns: 1fr; }
  .church-page-name { font-size: 1.3rem; }
  .church-page-logo { width: 56px; height: 56px; }
}
</style>
