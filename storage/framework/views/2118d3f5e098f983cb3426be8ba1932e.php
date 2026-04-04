<script>
var API = '/api';
var PAGES = ['home','blog','events','prayers','library','studies','sermons','giving','ministries','reviews','testimonies','contact','about'];
var NAV_LABELS = {home:'Home',blog:'Blog',events:'Events',prayers:'Prayers',library:'Library',studies:'Bible Study',sermons:'Sermons',giving:'Giving',ministries:'Ministries',reviews:'Reviews',testimonies:'Testimonies',contact:'Contact',about:'About'};
var NAV_ICONS = {home:'\u2302',blog:'\uD83D\uDCF0',events:'\uD83D\uDCC5',prayers:'\uD83D\uDE4F',library:'\uD83D\uDCDA',studies:'\uD83D\uDCD6',sermons:'\uD83C\uDF99\uFE0F',giving:'\uD83D\uDC9B',ministries:'\uD83E\uDD1D',reviews:'\u2B50',testimonies:'\u271D',contact:'\u2709\uFE0F',about:'\u26EA'};
var BOOK_ICONS = ['\uD83D\uDCD8','\uD83D\uDCD7','\uD83D\uDCD5','\uD83D\uDCD9','\uD83D\uDCD3','\uD83D\uDCD4','\uD83D\uDCD2','\uD83D\uDCDA'];
var MINISTRY_ICONS = ['\uD83E\uDD1D','\uD83C\uDFB5','\uD83D\uDC76','\uD83C\uDF93','\uD83C\uDF5E','\uD83C\uDFE5','\uD83D\uDCD6','\uD83C\uDF0D','\uD83D\uDC92','\uD83C\uDFA8'];
var currentPage = 'home';
var frontendMenus = null;
var frontendCategories = [];
var prayedIds = {};
var selectedRating = 0;
var selectedGiving = null;
var allBooks = [];
var bookFilter = 'All';
var churchSettings = {};
var widgetConfig = null;

/* ===== THEME TOGGLE ===== */
function getTheme() {
  return localStorage.getItem('church-theme') || 'dark';
}
function setTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem('church-theme', theme);
  var btn = document.getElementById('theme-toggle');
  btn.innerHTML = theme === 'dark' ? '\u263E' : '\u2600';
  btn.title = theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
  // Update PWA theme-color
  var meta = document.querySelector('meta[name="theme-color"]');
  if (meta) meta.content = theme === 'dark' ? '#0C0E12' : '#F8F6F2';
}
function toggleTheme() {
  setTheme(getTheme() === 'dark' ? 'light' : 'dark');
}
// Apply saved theme on load
(function() { setTheme(getTheme()); })();

/* ===== API ===== */
function apiCall(path, opts) {
  opts = opts || {};
  var defaultHeaders = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
  };
  if (opts.headers) {
    Object.keys(opts.headers).forEach(function(k) { defaultHeaders[k] = opts.headers[k]; });
    delete opts.headers;
  }
  return fetch(API + path, Object.assign({ headers: defaultHeaders }, opts)).then(function(res) {
    if (!res.ok && res.status === 404) return null;
    return res.json();
  }).catch(function(e) { console.error('API error:', path, e); return null; });
}

/* ===== NAV ===== */
function buildNav() {
  var nl = document.getElementById('nav-links');
  var ml = document.getElementById('mobile-links');
  nl.innerHTML = ''; ml.innerHTML = '';

  // If we have admin-configured menus, use them
  if (frontendMenus && frontendMenus.items && frontendMenus.items.length > 0) {
    buildMenuFromConfig(nl, ml, frontendMenus.items);
    return;
  }

  // Default fallback nav
  PAGES.forEach(function(p) {
    var cls = p === currentPage ? 'nav-link active' : 'nav-link';
    nl.innerHTML += '<li><button class="' + cls + '" onclick="navigate(\'' + p + '\')">' + NAV_LABELS[p] + '</button></li>';
    var mcls = p === currentPage ? 'mobile-nav-item active' : 'mobile-nav-item';
    ml.innerHTML += '<button class="' + mcls + '" onclick="navigate(\'' + p + '\')"><span class="mobile-nav-icon">' + NAV_ICONS[p] + '</span>' + NAV_LABELS[p] + '</button>';
  });
}

function buildMenuFromConfig(nl, ml, menuItems) {
  menuItems.forEach(function(item) {
    var hasChildren = (item.children && item.children.length > 0);
    var target = resolveMenuTarget(item);
    var isActive = target.page && target.page === currentPage;

    // Desktop nav
    if (hasChildren) {
      var li = '<li class="nav-dropdown-wrap">';
      li += '<button class="nav-link' + (isActive ? ' active' : '') + '" onclick="' + target.action + '">' + esc(item.label);
      li += ' <span class="nav-dropdown-arrow">&#9662;</span></button>';
      li += '<div class="nav-dropdown">';
      item.children.forEach(function(child) {
        var ct = resolveMenuTarget(child);
        li += '<button class="nav-dropdown-item" onclick="' + ct.action + '">' + esc(child.label) + '</button>';
      });
      // If type is category, also show subcategories from API
      if (item.type === 'category') {
        var catChildren = getCategoryChildren(item.target);
        catChildren.forEach(function(sc) {
          li += '<button class="nav-dropdown-item" onclick="navigateToCategoryBlog(\'' + esc(sc.slug) + '\')">' + esc(sc.name) + '</button>';
        });
      }
      li += '</div></li>';
      nl.innerHTML += li;
    } else {
      nl.innerHTML += '<li><button class="nav-link' + (isActive ? ' active' : '') + '" onclick="' + target.action + '">' + esc(item.label) + '</button></li>';
    }

    // Mobile nav
    var mcls = isActive ? 'mobile-nav-item active' : 'mobile-nav-item';
    ml.innerHTML += '<button class="' + mcls + '" onclick="' + target.action + '"><span class="mobile-nav-icon">' + (NAV_ICONS[target.page] || '&#9679;') + '</span>' + esc(item.label) + '</button>';
    if (hasChildren) {
      item.children.forEach(function(child) {
        var ct = resolveMenuTarget(child);
        ml.innerHTML += '<button class="mobile-nav-item mobile-nav-sub" onclick="' + ct.action + '"><span class="mobile-nav-icon">&#8627;</span>' + esc(child.label) + '</button>';
      });
    }
  });
}

function resolveMenuTarget(item) {
  var type = item.type || 'link';
  var target = item.target || '';
  if (type === 'page') {
    // Navigate to a SPA page by slug
    var pageSlug = target.replace(/^\//, '');
    if (PAGES.indexOf(pageSlug) !== -1) {
      return { action: "navigate('" + pageSlug + "')", page: pageSlug };
    }
    return { action: "navigate('home')", page: 'home' };
  }
  if (type === 'post') {
    return { action: "viewBlogPost('" + esc(target) + "')", page: 'blog' };
  }
  if (type === 'category') {
    return { action: "navigateToCategoryBlog('" + esc(target) + "')", page: 'blog' };
  }
  if (type === 'link') {
    if (target.indexOf('http') === 0) {
      return { action: "window.open('" + esc(target) + "','_blank')", page: '' };
    }
    var pg = target.replace(/^[#\/]+/, '');
    if (PAGES.indexOf(pg) !== -1) {
      return { action: "navigate('" + pg + "')", page: pg };
    }
    return { action: "navigate('home')", page: 'home' };
  }
  return { action: "navigate('home')", page: 'home' };
}

function getCategoryChildren(slugOrId) {
  var result = [];
  frontendCategories.forEach(function(cat) {
    if ((cat.slug === slugOrId || String(cat.id) === String(slugOrId)) && cat.children) {
      result = cat.children.filter(function(c) { return c.is_active; });
    }
  });
  return result;
}

function navigateToCategoryBlog(categorySlug) {
  navigate('blog');
  setTimeout(function() {
    setBlogCategory(categorySlug);
  }, 200);
}

function loadFrontendMenus() {
  return apiCall('/menus/header').then(function(res) {
    if (res && res.data) {
      frontendMenus = res.data;
      buildNav();
    }
  }).catch(function() {});
}

function loadFrontendCategories() {
  return apiCall('/categories?tree=1').then(function(res) {
    if (res && res.data) {
      frontendCategories = res.data;
    }
  }).catch(function() {});
}

function navigate(page, opts) {
  opts = opts || {};

  // SSR pages - redirect to server-rendered clean URLs
  var ssrPages = ['ministries', 'bible-studies', 'library', 'about'];
  if (ssrPages.indexOf(page) !== -1 && !opts.skipUrl) {
    window.location.href = '/' + page;
    return;
  }

  currentPage = page;
  PAGES.forEach(function(p) {
    document.getElementById('page-' + p).classList.toggle('active', p === page);
  });
  // Handle special sub-pages
  var blogDetail = document.getElementById('page-blog-detail');
  if (blogDetail) blogDetail.classList.toggle('active', page === 'blog-detail');
  var churchesPage = document.getElementById('page-churches');
  if (churchesPage) churchesPage.classList.toggle('active', page === 'churches');
  var churchDetail = document.getElementById('page-church-detail');
  if (churchDetail) churchDetail.classList.toggle('active', page === 'church-detail');
  if (page === 'blog') loadBlogPage();
  if (page === 'churches') loadChurchDirectory();
  buildNav();
  closeMobile();
  // Update URL with History API (clean URLs)
  if (!opts.skipUrl) {
    if (page === 'blog-detail' && opts.slug) {
      history.pushState({ page: 'blog-detail', slug: opts.slug }, '', '/blog/' + opts.slug);
    } else if (page === 'church-detail' && opts.slug) {
      history.pushState({ page: 'church-detail', slug: opts.slug }, '', '/church/' + opts.slug);
    } else if (page === 'home') {
      history.pushState({ page: 'home' }, '', '/');
    } else {
      history.pushState({ page: page }, '', '/' + page);
    }
  }
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Handle clean URL routing (HTML5 History API)
function handleRoute() {
  var path = window.location.pathname.replace(/^\/+/, '').replace(/\/+$/, '');

  // Legacy hash support: redirect old hash URLs to clean URLs
  var hash = window.location.hash;
  if (hash && hash.indexOf('#/') === 0) {
    var cleanPath = hash.replace('#/', '');
    history.replaceState(null, '', '/' + cleanPath);
    path = cleanPath;
  }

  if (!path || path === '') { return; }

  // Blog post permalink: /blog/post-slug
  if (path.indexOf('blog/') === 0) {
    var slug = path.substring(5);
    if (slug && slug !== '') {
      viewBlogPost(slug);
      return;
    }
  }
  // Church detail permalink: /church/church-slug
  if (path.indexOf('church/') === 0) {
    var churchSlug = path.substring(7);
    if (churchSlug && churchSlug !== '') {
      viewChurchPage(churchSlug);
      return;
    }
  }
  // Churches directory: /churches
  if (path === 'churches') {
    navigate('churches', { skipUrl: true });
    return;
  }
  // SSR pages - these are now server-rendered, no SPA handling needed
  var ssrPrefixes = ['page/', 'ministries', 'bible-studies', 'library', 'about'];
  for (var i = 0; i < ssrPrefixes.length; i++) {
    if (path.indexOf(ssrPrefixes[i]) === 0) {
      return; // Let the server handle these routes
    }
  }
  // Page navigation: /events, /blog, etc.
  if (PAGES.indexOf(path) !== -1) {
    navigate(path, { skipUrl: true });
  }
}

// Handle browser back/forward buttons
window.addEventListener('popstate', function() {
  handleRoute();
});

/* ===== MOBILE MENU ===== */
function toggleMobile() {
  var menu = document.getElementById('mobile-menu');
  menu.classList.toggle('open');
  document.body.style.overflow = menu.classList.contains('open') ? 'hidden' : '';
}
function closeMobile() {
  document.getElementById('mobile-menu').classList.remove('open');
  document.body.style.overflow = '';
}

/* ===== UTILS ===== */
function showToast(msg) {
  var t = document.getElementById('toast');
  t.textContent = msg; t.classList.add('show');
  setTimeout(function() { t.classList.remove('show'); }, 3000);
}
function openModal(type) { document.getElementById('modal-' + type).classList.add('open'); document.body.style.overflow = 'hidden'; }
function closeModal(type) { document.getElementById('modal-' + type).classList.remove('open'); document.body.style.overflow = ''; }
function renderStars(rating) { return '\u2605'.repeat(rating) + '\u2606'.repeat(5 - rating); }
function buildStarInput() {
  var c = document.getElementById('star-input'); c.innerHTML = '';
  for (var i = 1; i <= 5; i++) {
    var b = document.createElement('button');
    b.textContent = i <= selectedRating ? '\u2605' : '\u2606';
    b.className = i <= selectedRating ? 'filled' : '';
    b.setAttribute('data-val', i);
    b.onclick = function() { selectedRating = parseInt(this.getAttribute('data-val')); buildStarInput(); };
    c.appendChild(b);
  }
}
function fmtDate(d) {
  if (!d) return '';
  return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}
function esc(s) { if (!s) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

/* ===== CARD RENDERERS ===== */
function prayerCard(p) {
  var prayed = prayedIds[p.id];
  return '<div class="card"><div class="prayer-name">' + esc(p.name) + '</div>' +
    (p.subject ? '<div style="font-size:0.85rem;color:var(--gold);margin-bottom:0.3rem;font-weight:600">' + esc(p.subject) + '</div>' : '') +
    '<div class="prayer-text">' + esc(p.description || p.request) + '</div>' +
    '<div class="prayer-footer"><span class="prayer-date">' + fmtDate(p.created_at) + '</span>' +
    '<button class="pray-btn ' + (prayed ? 'prayed' : '') + '" onclick="doPray(' + p.id + ')">\uD83D\uDE4F ' + (p.prayer_count || 0) + ' ' + (prayed ? 'Prayed' : 'Pray') + '</button></div></div>';
}
function eventCard(e) {
  return '<div class="card"><h3 class="card-title">' + esc(e.title) + '</h3>' +
    '<p class="card-meta">\uD83D\uDCC5 ' + fmtDate(e.start_date) + (e.start_time ? ' \u2022 \u23F0 ' + e.start_time.slice(0,5) : '') + '</p>' +
    (e.location ? '<p class="card-meta">\uD83D\uDCCD ' + esc(e.location) + '</p>' : '') +
    '<p class="card-desc">' + esc(e.description || '') + '</p></div>';
}
function bookCard(b, i) {
  return '<div class="book-card"><div class="book-cover">' + BOOK_ICONS[i % BOOK_ICONS.length] + '</div>' +
    '<div class="book-info"><div class="book-title">' + esc(b.title) + '</div>' +
    '<div class="book-author">' + esc(b.author) + '</div>' +
    (b.category ? '<span class="book-category">' + esc(b.category) + '</span>' : '') +
    (b.pages ? '<div class="card-desc">' + b.pages + ' pages</div>' : '') +
    '<div class="book-actions">' + (b.pdf_file ? '<button class="book-btn" onclick="openPdfViewer(\'/storage/' + esc(b.pdf_file) + '\', \'' + esc(b.title).replace(/'/g, "\\'") + '\')">\uD83D\uDCD6 Read</button>' : '<button class="book-btn" disabled>\uD83D\uDCD6 Read</button>') +
    (b.pdf_file ? '<button class="book-btn book-btn-pdf" onclick="downloadBook(' + b.id + ')">\uD83D\uDCE5 PDF</button>' : '') +
    '</div></div></div>';
}
function studyCard(s) {
  return '<div class="card"><span class="card-badge badge-study">' + esc(s.category || 'Study') + '</span>' +
    '<h3 class="card-title">' + esc(s.title) + '</h3>' +
    '<p class="card-desc">' + esc(s.description || '') + '</p>' +
    '<div class="study-meta" style="margin-top:0.8rem">' +
    (s.difficulty ? '<span>\uD83D\uDCCA ' + esc(s.difficulty) + '</span>' : '') +
    (s.duration_minutes ? '<span>\u23F1 ' + s.duration_minutes + ' min</span>' : '') +
    (s.author ? '<span>\uD83D\uDC64 ' + esc(s.author) + '</span>' : '') +
    '</div>' +
    (s.scripture_reference ? '<p class="card-meta" style="margin-top:0.4rem">\uD83D\uDCD6 ' + esc(s.scripture_reference) + '</p>' : '') +
    '</div>';
}
function sermonCard(s) {
  var playUrl = s.video_url || s.audio_url || '';
  return '<div class="sermon-card" style="margin-bottom:1rem">' +
    '<button class="sermon-play"' + (playUrl ? ' onclick="window.open(\'' + esc(playUrl) + '\')"' : '') + '>\u25B6</button>' +
    '<div style="flex:1"><h3 class="card-title">' + esc(s.title) + '</h3>' +
    '<p class="card-meta">' + esc(s.speaker) + ' \u2022 ' + fmtDate(s.sermon_date) + (s.duration ? ' \u2022 ' + s.duration : '') + '</p>' +
    '<p class="card-desc">' + esc(s.description || '') + '</p>' +
    (s.series ? '<span class="card-badge badge-worship" style="margin-top:0.4rem">' + esc(s.series) + '</span>' : '') +
    '</div></div>';
}
function reviewCard(r) {
  return '<div class="card"><div class="review-stars">' + renderStars(r.rating) + '</div>' +
    (r.title ? '<div class="card-title" style="font-size:0.95rem;margin-bottom:0.3rem">' + esc(r.title) + '</div>' : '') +
    '<div class="review-text">\u201C' + esc(r.content) + '\u201D</div>' +
    '<div class="review-author">\u2014 ' + esc(r.name) + ' \u2022 ' + fmtDate(r.created_at) + '</div></div>';
}

/* ===== ACTIONS ===== */
function doPray(id) {
  if (prayedIds[id]) return;
  apiCall('/prayer-requests/' + id + '/pray', { method: 'POST' }).then(function(res) {
    if (res) { prayedIds[id] = true; showToast('\uD83D\uDE4F Thank you for praying.'); loadPrayers(); }
  });
}
function submitPrayer() {
  var name = document.getElementById('prayer-name').value;
  var subject = document.getElementById('prayer-subject').value;
  var desc = document.getElementById('prayer-request').value;
  var isAnon = document.getElementById('prayer-anon').checked;
  var isPublic = document.getElementById('prayer-public').checked;
  if (!subject.trim() || !desc.trim()) { showToast('Please enter a subject and prayer request.'); return; }
  apiCall('/prayer-requests', {
    method: 'POST',
    body: JSON.stringify({ name: isAnon ? 'Anonymous' : (name || 'Church Member'), subject: subject, description: desc, is_public: isPublic })
  }).then(function(res) {
    if (res && res.prayer_request) {
      closeModal('prayer');
      document.getElementById('prayer-name').value = '';
      document.getElementById('prayer-subject').value = '';
      document.getElementById('prayer-request').value = '';
      document.getElementById('prayer-anon').checked = false;
      document.getElementById('prayer-public').checked = true;
      showToast('\uD83D\uDE4F Prayer request submitted. We\'re praying with you.');
      loadPrayers();
    } else {
      showToast('Failed to submit prayer request. Please try again.');
    }
  });
}
function submitReview() {
  var name = document.getElementById('review-name').value;
  var email = document.getElementById('review-email').value;
  var title = document.getElementById('review-title').value;
  var text = document.getElementById('review-text').value;
  if (!name.trim() || !email.trim() || !title.trim() || !text.trim() || !selectedRating) { showToast('Please fill in all fields and add a rating.'); return; }
  apiCall('/reviews', {
    method: 'POST',
    body: JSON.stringify({ name: name, email: email, rating: selectedRating, title: title, content: text })
  }).then(function(res) {
    if (res && res.success) {
      closeModal('review');
      document.getElementById('review-name').value = '';
      document.getElementById('review-email').value = '';
      document.getElementById('review-title').value = '';
      document.getElementById('review-text').value = '';
      selectedRating = 0; buildStarInput();
      showToast('\u2B50 Thank you for your review! It will appear once approved.');
      loadReviews();
    } else {
      showToast('Failed to submit review. Please try again.');
    }
  });
}
function submitDonation() {
  var custom = document.getElementById('custom-amount').value;
  var amount = custom ? parseFloat(custom) : selectedGiving;
  if (!amount || amount < 1) { showToast('Please select or enter an amount.'); return; }
  showToast('\uD83D\uDC9B Thank you for your generous gift of $' + amount + '! (Payment integration coming soon)');
}
function downloadBook(id) {
  apiCall('/books/' + id + '/download').then(function(res) {
    if (res && res.success && res.data && res.data.download_url) { window.open(res.data.download_url); }
    else { showToast('PDF not available for this book.'); }
  });
}
function filterBooks() {
  var search = document.getElementById('book-search').value.toLowerCase();
  var filtered = allBooks.filter(function(b) {
    var matchCat = bookFilter === 'All' || b.category === bookFilter;
    var matchSearch = !search || b.title.toLowerCase().indexOf(search) !== -1 || (b.author && b.author.toLowerCase().indexOf(search) !== -1);
    return matchCat && matchSearch;
  });
  document.getElementById('all-books').innerHTML = filtered.map(function(b, i) { return bookCard(b, i); }).join('') || '<p style="color:var(--text-muted)">No books found.</p>';
}
function setBookFilter(cat) {
  bookFilter = cat;
  document.querySelectorAll('.filter-btn').forEach(function(b) { b.classList.toggle('active', b.dataset.cat === cat); });
  filterBooks();
}

/* ===== DATA LOADERS ===== */
function loadAnnouncements() {
  return apiCall('/announcements/active').then(function(res) {
    var items = (res && res.data) ? res.data : [];
    if (items.length > 0) {
      document.getElementById('ticker-text').textContent = items.map(function(a) {
        return '\uD83D\uDCE2 ' + a.title + (a.content ? ': ' + a.content : '');
      }).join('   \u2022   ');
    }
  });
}
function loadVerse() {
  return apiCall('/verses/today').then(function(res) {
    if (res && res.verse) {
      document.getElementById('verse-text').textContent = '\u201C' + res.verse.verse_text + '\u201D';
      document.getElementById('verse-ref').textContent = '\u2014 ' + res.verse.reference;
    } else {
      document.getElementById('verse-text').textContent = '\u201CFor God so loved the world, that he gave his only Son, that whoever believes in him should not perish but have eternal life.\u201D';
      document.getElementById('verse-ref').textContent = '\u2014 John 3:16';
    }
  });
}
function loadBlessing() {
  return apiCall('/blessings/today').then(function(res) {
    if (res && res.blessing) {
      document.getElementById('blessing-title').textContent = res.blessing.title;
      document.getElementById('blessing-text').textContent = res.blessing.content;
      document.getElementById('blessing-author').textContent = res.blessing.author ? '\u2014 ' + res.blessing.author : '';
    }
  });
}
function loadPosts() {
  return apiCall('/posts/featured').then(function(res) {
    var posts = (res && res.data && res.data.data) ? res.data.data : [];
    if (posts.length > 0) {
      document.getElementById('ticker-text').textContent = posts.map(function(p) { return '\uD83D\uDCE2 ' + p.title + (p.excerpt ? ': ' + p.excerpt : ''); }).join('   \u2022   ');
      document.getElementById('home-posts').innerHTML = renderHomePosts(posts);
    }
  });
}
function loadPrayers() {
  return apiCall('/prayer-requests/public').then(function(res) {
    var prayers = (res && res.data) ? res.data : [];
    document.getElementById('home-prayers').innerHTML = prayers.slice(0, 3).map(prayerCard).join('') || '<p class="loading">No prayer requests yet.</p>';
    document.getElementById('all-prayers').innerHTML = prayers.map(prayerCard).join('') || '<p class="loading">No prayer requests yet.</p>';
  });
}
function loadEvents() {
  return apiCall('/events/upcoming').then(function(res) {
    var events = (res && res.data) ? res.data : [];
    document.getElementById('home-events').innerHTML = events.slice(0, 3).map(eventCard).join('') || '<p class="loading">No upcoming events.</p>';
    document.getElementById('all-events').innerHTML = events.map(eventCard).join('') || '<p class="loading">No upcoming events.</p>';
  });
}
function loadBooks() {
  return apiCall('/books/featured').then(function(res) {
    allBooks = (res && res.data && res.data.data) ? res.data.data : [];
    var cats = ['All'];
    allBooks.forEach(function(b) { if (b.category && cats.indexOf(b.category) === -1) cats.push(b.category); });
    document.getElementById('book-filters').innerHTML = cats.map(function(c) {
      return '<button class="filter-btn ' + (c === bookFilter ? 'active' : '') + '" data-cat="' + c + '" onclick="setBookFilter(\'' + c + '\')">' + c + '</button>';
    }).join('');
    filterBooks();
  });
}
function loadStudies() {
  return apiCall('/bible-studies/featured').then(function(res) {
    var studies = (res && res.data && res.data.data) ? res.data.data : [];
    document.getElementById('all-studies').innerHTML = studies.map(studyCard).join('') || '<p class="loading">No active studies.</p>';
  });
}
function loadSermons() {
  return apiCall('/sermons/featured').then(function(res) {
    var sermons = (res && res.data && res.data.data) ? res.data.data : [];
    document.getElementById('all-sermons').innerHTML = sermons.map(sermonCard).join('') || '<p class="loading">No sermons yet.</p>';
    if (sermons.length > 0) {
      document.getElementById('home-sermon').innerHTML =
        '<div class="section-header"><h2 class="section-title">\uD83C\uDF99\uFE0F Latest Sermon</h2>' +
        '<button class="section-action" onclick="navigate(\'sermons\')">All Sermons</button></div>' +
        sermonCard(sermons[0]);
    }
  });
}
function loadReviews() {
  return apiCall('/reviews/approved').then(function(res) {
    var reviews = [];
    if (res && res.data) {
      reviews = res.data.data ? res.data.data : (Array.isArray(res.data) ? res.data : []);
    }
    var total = reviews.length;
    var sum = 0;
    reviews.forEach(function(r) { sum += (r.rating || 0); });
    var avg = total > 0 ? (sum / total).toFixed(1) : '0';
    document.getElementById('avg-rating').textContent = avg;
    document.getElementById('avg-stars').innerHTML = '<span class="review-stars">' + renderStars(Math.round(parseFloat(avg))) + '</span>';
    document.getElementById('total-reviews').textContent = total + ' reviews';
    document.getElementById('all-reviews').innerHTML = reviews.map(reviewCard).join('') || '<p class="loading">No reviews yet.</p>';
  });
}
function loadChurchSettings() {
  return apiCall('/settings').then(function(res) {
    churchSettings = (res && res.data) ? res.data : {};
    // Support both column naming conventions (church_address vs address)
    var s = churchSettings;
    var name = s.church_name || document.getElementById('nav-church-name').textContent;
    var addr = s.address || s.church_address || '';
    var phone = s.phone || s.church_phone || '';
    var email = s.email || s.church_email || '';
    var desc = s.mission_statement || s.description || s.church_description || '';
    document.getElementById('nav-church-name').textContent = name;
    document.getElementById('mobile-brand-name').textContent = name;
    document.getElementById('footer-name').textContent = name;
    document.getElementById('footer-info').textContent = addr + (phone ? ' \u2022 ' + phone : '');
    document.title = name;
    document.getElementById('about-hero').innerHTML =
      '<h2 class="blessing-title" style="font-size:1.8rem">' + esc(name) + '</h2>' +
      (s.tagline ? '<p style="color:var(--gold);font-style:italic;margin-bottom:0.5rem">' + esc(s.tagline) + '</p>' : '') +
      '<p class="blessing-text" style="font-size:1.05rem">' + esc(desc) + '</p>';
    populateContactInfo();
    document.getElementById('about-info').innerHTML =
      '<div class="info-card"><h3 class="info-card-title">\u26EA Service Times</h3><div class="info-card-text">' + esc(s.service_times || '') + '</div></div>' +
      '<div class="info-card"><h3 class="info-card-title">\uD83D\uDCCD Location</h3><p class="info-card-text">' + esc(addr) + '</p>' +
      (s.city ? '<p class="info-card-text">' + esc(s.city) + (s.state ? ', ' + esc(s.state) : '') + (s.zip_code ? ' ' + esc(s.zip_code) : '') + '</p>' : '') +
      '<p class="info-card-text" style="margin-top:0.4rem">\uD83D\uDCDE ' + esc(phone) + '</p><p class="info-card-text">\u2709\uFE0F ' + esc(email) + '</p></div>' +
      '<div class="info-card"><h3 class="info-card-title">\uD83D\uDC64 Leadership</h3><p class="info-card-text">Lead Pastor: ' + esc(s.pastor_name || '') + '</p>' +
      (s.pastor_title ? '<p class="info-card-text">' + esc(s.pastor_title) + '</p>' : '') + '</div>' +
      '<div class="info-card"><h3 class="info-card-title">\uD83C\uDF10 Connect Online</h3><p class="info-card-text">Follow us on social media and stay connected.</p><div class="social-links">' +
      (s.facebook_url ? '<a class="social-link" href="' + esc(s.facebook_url) + '" target="_blank">Facebook</a>' : '') +
      (s.youtube_url ? '<a class="social-link" href="' + esc(s.youtube_url) + '" target="_blank">YouTube</a>' : '') +
      (s.instagram_url ? '<a class="social-link" href="' + esc(s.instagram_url) + '" target="_blank">Instagram</a>' : '') +
      (s.twitter_url ? '<a class="social-link" href="' + esc(s.twitter_url) + '" target="_blank">Twitter</a>' : '') +
      '</div></div>';
  });
}
function loadMinistries() {
  return apiCall('/ministries').then(function(res) {
    var list = (res && res.data && res.data.data) ? res.data.data : [];
    document.getElementById('all-ministries').innerHTML = list.map(function(m, i) {
      return '<div class="volunteer-card" onclick="showToast(\'\uD83D\uDCCB Contact ' + esc(m.leader_name || 'the church') + ' to join ' + esc(m.name) + '!\')">' +
        '<div class="volunteer-icon">' + MINISTRY_ICONS[i % MINISTRY_ICONS.length] + '</div>' +
        '<div class="volunteer-name">' + esc(m.name) + '</div>' +
        (m.category ? '<div class="volunteer-spots">' + esc(m.category) + '</div>' : '') +
        (m.meeting_schedule ? '<div class="volunteer-spots">' + esc(m.meeting_schedule) + '</div>' : '') +
        '</div>';
    }).join('') || '<p class="loading">No ministries listed.</p>';
  });
}
function buildGiving() {
  var amounts = [10, 25, 50, 100, 250, 500];
  document.getElementById('giving-amounts').innerHTML = amounts.map(function(a) {
    return '<button class="giving-amount" onclick="selectGiving(' + a + ', this)">$' + a + '</button>';
  }).join('');
}
function selectGiving(amount, btn) {
  selectedGiving = amount;
  document.querySelectorAll('.giving-amount').forEach(function(b) { b.classList.remove('selected'); });
  btn.classList.add('selected');
  document.getElementById('custom-amount').value = '';
}

/* ===== TESTIMONIES ===== */
function testimonyCard(t) {
  return '<div class="card testimony-card" onclick="viewTestimony(\'' + esc(t.slug) + '\')" style="cursor:pointer">' +
    (t.featured_image ? '<div style="margin:-1.4rem -1.4rem 1rem;border-radius:var(--radius-lg) var(--radius-lg) 0 0;overflow:hidden;height:160px"><img src="/storage/' + esc(t.featured_image) + '" alt="' + esc(t.name) + '" style="width:100%;height:100%;object-fit:cover"></div>' : '') +
    '<div class="prayer-name" style="display:flex;align-items:center;gap:8px">\u271D ' + esc(t.name) + '</div>' +
    (t.born_again_date ? '<p class="card-meta">\uD83D\uDD25 Born Again: ' + fmtDate(t.born_again_date) + '</p>' : '') +
    (t.baptism_date ? '<p class="card-meta">\uD83D\uDCA7 Baptized: ' + fmtDate(t.baptism_date) + '</p>' : '') +
    '<p class="card-desc" style="margin-top:0.6rem">\u201C' + esc(t.excerpt || (t.testimony || '').substring(0, 150)) + '...\u201D</p>' +
    '<div class="prayer-footer" style="margin-top:0.8rem"><span class="prayer-date">' + fmtDate(t.published_at || t.created_at) + '</span>' +
    '<span style="font-size:0.78rem;color:var(--text-muted)">\uD83D\uDC41 ' + (t.view_count || 0) + ' views</span></div></div>';
}
function loadTestimonies() {
  return apiCall('/testimonies/approved').then(function(res) {
    var testimonies = (res && res.data) ? res.data : [];
    document.getElementById('all-testimonies').innerHTML = testimonies.map(testimonyCard).join('') || '<p class="loading">No testimonies shared yet. Be the first!</p>';
  });
}
function viewTestimony(slug) {
  apiCall('/testimonies/' + slug).then(function(res) {
    if (res && res.success && res.data) {
      var t = res.data;
      var html = '<div class="modal-content" style="max-width:640px">' +
        '<div class="modal-title">\u271D ' + esc(t.name) + '\'s Testimony <button class="modal-close" onclick="closeModal(\'testimony-view\')">\u2715</button></div>' +
        (t.featured_image ? '<div style="margin-bottom:1rem;border-radius:var(--radius-md);overflow:hidden"><img src="/storage/' + esc(t.featured_image) + '" alt="' + esc(t.name) + '" style="width:100%;max-height:250px;object-fit:cover"></div>' : '') +
        '<div style="display:flex;gap:1.5rem;margin-bottom:1rem;flex-wrap:wrap">' +
        (t.born_again_date ? '<div style="font-size:0.85rem;color:var(--text-secondary)"><strong style="color:var(--gold)">\uD83D\uDD25 Born Again:</strong> ' + fmtDate(t.born_again_date) + '</div>' : '') +
        (t.baptism_date ? '<div style="font-size:0.85rem;color:var(--text-secondary)"><strong style="color:var(--gold)">\uD83D\uDCA7 Baptized:</strong> ' + fmtDate(t.baptism_date) + '</div>' : '') +
        '</div>' +
        '<div style="font-family:var(--font-elegant);font-size:1.05rem;line-height:1.8;color:var(--text-primary);white-space:pre-wrap">' + esc(t.testimony) + '</div>' +
        '<div style="margin-top:1.2rem;padding-top:0.8rem;border-top:1px solid var(--border);font-size:0.82rem;color:var(--text-muted);display:flex;justify-content:space-between">' +
        '<span>' + fmtDate(t.published_at || t.created_at) + '</span>' +
        '<span>\uD83D\uDC41 ' + (t.view_count || 0) + ' views</span></div></div>';
      // Create a dynamic view modal
      var overlay = document.getElementById('modal-testimony-view');
      if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.id = 'modal-testimony-view';
        overlay.addEventListener('click', function(e) { if (e.target === overlay) closeModal('testimony-view'); });
        document.body.appendChild(overlay);
      }
      overlay.innerHTML = html;
      openModal('testimony-view');
    }
  });
}
function submitTestimony() {
  var name = document.getElementById('testimony-name').value;
  var bornAgain = document.getElementById('testimony-born-again').value;
  var baptism = document.getElementById('testimony-baptism').value;
  var text = document.getElementById('testimony-text').value;
  if (!name.trim() || !text.trim()) { showToast('Please enter your name and testimony.'); return; }
  if (text.trim().length < 20) { showToast('Please write at least 20 characters for your testimony.'); return; }
  apiCall('/testimonies', {
    method: 'POST',
    body: JSON.stringify({ name: name, born_again_date: bornAgain || null, baptism_date: baptism || null, testimony: text })
  }).then(function(res) {
    if (res && res.success) {
      closeModal('testimony');
      document.getElementById('testimony-name').value = '';
      document.getElementById('testimony-born-again').value = '';
      document.getElementById('testimony-baptism').value = '';
      document.getElementById('testimony-text').value = '';
      showToast('\u271D Thank you for sharing your testimony! It will appear after approval.');
      loadTestimonies();
    } else {
      showToast(res && res.message ? res.message : 'Failed to submit testimony. Please try again.');
    }
  });
}

/* ===== CONTACT FORM ===== */
function submitContact() {
  var name = document.getElementById('contact-name').value;
  var email = document.getElementById('contact-email').value;
  var phone = document.getElementById('contact-phone').value;
  var subject = document.getElementById('contact-subject').value;
  var message = document.getElementById('contact-message').value;
  if (!name.trim() || !email.trim() || !subject.trim() || !message.trim()) {
    showToast('Please fill in all required fields.'); return;
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showToast('Please enter a valid email address.'); return; }
  apiCall('/contact', {
    method: 'POST',
    body: JSON.stringify({ name: name, email: email, phone: phone || null, subject: subject, message: message })
  }).then(function(res) {
    if (res && res.success) {
      document.getElementById('contact-name').value = '';
      document.getElementById('contact-email').value = '';
      document.getElementById('contact-phone').value = '';
      document.getElementById('contact-subject').value = '';
      document.getElementById('contact-message').value = '';
      showToast('\u2709\uFE0F Message sent! We will get back to you soon.');
    } else {
      showToast(res && res.message ? res.message : 'Failed to send message. Please try again.');
    }
  });
}
function populateContactInfo() {
  var s = churchSettings;
  var addr = s.address || s.church_address || 'Address not available';
  var city = s.city ? s.city + (s.state ? ', ' + s.state : '') + (s.zip_code ? ' ' + s.zip_code : '') : '';
  document.getElementById('contact-address').textContent = addr + (city ? '\n' + city : '');
  document.getElementById('contact-phone-info').textContent = s.phone || s.church_phone || 'Phone not available';
  document.getElementById('contact-email-info').textContent = s.email || s.church_email || 'Email not available';
  document.getElementById('contact-service-times').textContent = s.service_times || 'Check our About page for details';
}

/* ===== NEWSLETTER ===== */
var newsletterDismissed = false;
function showNewsletterPopup() {
  if (newsletterDismissed || sessionStorage.getItem('newsletter-dismissed') || localStorage.getItem('newsletter-subscribed')) return;
  document.getElementById('newsletter-popup').classList.add('show');
}
function closeNewsletterPopup() {
  document.getElementById('newsletter-popup').classList.remove('show');
  newsletterDismissed = true;
  sessionStorage.setItem('newsletter-dismissed', '1');
}
function submitNewsletter() {
  var email = document.getElementById('newsletter-email').value;
  var name = document.getElementById('newsletter-name').value;
  if (!email.trim()) { showToast('Please enter your email address.'); return; }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showToast('Please enter a valid email address.'); return; }
  apiCall('/newsletter/subscribe', {
    method: 'POST',
    body: JSON.stringify({ email: email, name: name || null })
  }).then(function(res) {
    if (res && res.success) {
      closeNewsletterPopup();
      localStorage.setItem('newsletter-subscribed', '1');
      showToast('\uD83D\uDC8C Thank you for subscribing! Check your inbox soon.');
    } else {
      showToast(res && res.message ? res.message : 'Subscription failed. Please try again.');
    }
  });
}
function submitFooterNewsletter() {
  var email = document.getElementById('newsletter-footer-email').value;
  if (!email.trim()) { showToast('Please enter your email address.'); return; }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showToast('Please enter a valid email address.'); return; }
  apiCall('/newsletter/subscribe', {
    method: 'POST',
    body: JSON.stringify({ email: email })
  }).then(function(res) {
    if (res && res.success) {
      document.getElementById('newsletter-footer-email').value = '';
      localStorage.setItem('newsletter-subscribed', '1');
      showToast('\uD83D\uDC8C Subscribed! Thank you for joining our newsletter.');
    } else {
      showToast(res && res.message ? res.message : 'Subscription failed. Please try again.');
    }
  });
}
// Show newsletter popup after 15 seconds of browsing
setTimeout(function() { showNewsletterPopup(); }, 15000);

/* ===== PWA ===== */
var deferredPrompt = null;
function dismissPwaInstall() {
  document.getElementById('pwa-install').classList.remove('show');
  sessionStorage.setItem('pwa-dismissed', '1');
}
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js').catch(function(e) { console.log('SW registration skipped:', e.message); });
}
window.addEventListener('beforeinstallprompt', function(e) {
  e.preventDefault();
  deferredPrompt = e;
  if (!sessionStorage.getItem('pwa-dismissed')) {
    document.getElementById('pwa-install').classList.add('show');
  }
});
document.getElementById('pwa-install-btn').addEventListener('click', function() {
  if (deferredPrompt) {
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then(function() { deferredPrompt = null; dismissPwaInstall(); });
  }
});

/* ===== AUTH ===== */
var authToken = localStorage.getItem('auth_token') || null;
var authUser = null;
try { authUser = JSON.parse(localStorage.getItem('auth_user')); } catch(e) {}

var customProfileFields = [];

function loadCustomProfileFields() {
  fetch(API + '/settings/profile-fields/public', { headers: { 'Accept': 'application/json' } })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      customProfileFields = (data.data || []).filter(function(f) { return f.enabled; });
      renderRegCustomFields();
      renderProfileCustomFields();
    }).catch(function() {});
}

function renderRegCustomFields() {
  var container = document.getElementById('reg-custom-fields');
  if (!container) return;
  container.innerHTML = '';
  customProfileFields.forEach(function(field) {
    var div = document.createElement('div');
    div.className = 'form-group';
    var label = document.createElement('label');
    label.className = 'form-label';
    label.textContent = field.label + (field.required ? ' *' : '');
    div.appendChild(label);

    var input;
    if (field.type === 'textarea') {
      input = document.createElement('textarea');
      input.className = 'form-input';
      input.rows = 3;
    } else if (field.type === 'select') {
      input = document.createElement('select');
      input.className = 'form-input';
      var defOpt = document.createElement('option');
      defOpt.value = ''; defOpt.textContent = 'Select...';
      input.appendChild(defOpt);
      (field.options || '').split(',').forEach(function(opt) {
        opt = opt.trim();
        if (opt) { var o = document.createElement('option'); o.value = opt; o.textContent = opt; input.appendChild(o); }
      });
    } else {
      input = document.createElement('input');
      input.className = 'form-input';
      input.type = field.type || 'text';
    }
    input.id = 'reg-cf-' + field.key;
    input.placeholder = field.placeholder || '';
    if (field.required) input.required = true;
    div.appendChild(input);
    container.appendChild(div);
  });
}

function renderProfileCustomFields() {
  var container = document.getElementById('profile-custom-fields');
  if (!container) return;
  container.innerHTML = '';
  if (customProfileFields.length === 0) return;
  var border = document.createElement('div');
  border.style.cssText = 'border-top:1px solid var(--border);margin:1rem 0;padding-top:1rem';
  var title = document.createElement('p');
  title.style.cssText = 'color:var(--text-muted);font-size:0.82rem;margin-bottom:0.8rem;font-weight:600';
  title.textContent = 'Profile Information';
  container.appendChild(border);
  container.appendChild(title);
  customProfileFields.forEach(function(field) {
    var div = document.createElement('div');
    div.className = 'form-group';
    var label = document.createElement('label');
    label.className = 'form-label';
    label.textContent = field.label + (field.required ? ' *' : '');
    div.appendChild(label);

    var input;
    if (field.type === 'textarea') {
      input = document.createElement('textarea');
      input.className = 'form-input';
      input.rows = 3;
    } else if (field.type === 'select') {
      input = document.createElement('select');
      input.className = 'form-input';
      var defOpt = document.createElement('option');
      defOpt.value = ''; defOpt.textContent = 'Select...';
      input.appendChild(defOpt);
      (field.options || '').split(',').forEach(function(opt) {
        opt = opt.trim();
        if (opt) { var o = document.createElement('option'); o.value = opt; o.textContent = opt; input.appendChild(o); }
      });
    } else {
      input = document.createElement('input');
      input.className = 'form-input';
      input.type = field.type || 'text';
    }
    input.id = 'profile-cf-' + field.key;
    input.placeholder = field.placeholder || '';
    div.appendChild(input);
    container.appendChild(div);
  });
}

// Load custom fields on page load
loadCustomProfileFields();

function updateAuthUI() {
  var loginBtn = document.getElementById('auth-login-btn');
  var userMenu = document.getElementById('auth-user-menu');
  var mobileLoginBtn = document.getElementById('mobile-login-btn');
  var mobileUserInfo = document.getElementById('mobile-user-info');
  if (authUser && authToken) {
    loginBtn.style.display = 'none';
    userMenu.style.display = '';
    var initials = (authUser.name || '?').split(' ').map(function(n) { return n[0]; }).join('').toUpperCase().slice(0, 2);
    document.getElementById('auth-avatar-text').textContent = initials;
    document.getElementById('auth-dropdown-name').textContent = authUser.name || '';
    document.getElementById('auth-dropdown-email').textContent = authUser.email || '';
    if (mobileLoginBtn) mobileLoginBtn.style.display = 'none';
    if (mobileUserInfo) { mobileUserInfo.style.display = ''; document.getElementById('mobile-user-name').textContent = authUser.name || ''; }
    // Show admin panel link only for admins
    var adminLink = document.getElementById('admin-panel-link');
    var mobileAdminLink = document.getElementById('mobile-admin-link');
    if (authUser.is_admin) {
      if (adminLink) adminLink.style.display = '';
      if (mobileAdminLink) mobileAdminLink.style.display = '';
    } else {
      if (adminLink) adminLink.style.display = 'none';
      if (mobileAdminLink) mobileAdminLink.style.display = 'none';
    }
  } else {
    loginBtn.style.display = '';
    userMenu.style.display = 'none';
    if (mobileLoginBtn) mobileLoginBtn.style.display = '';
    if (mobileUserInfo) mobileUserInfo.style.display = 'none';
    var adminLink = document.getElementById('admin-panel-link');
    var mobileAdminLink = document.getElementById('mobile-admin-link');
    if (adminLink) adminLink.style.display = 'none';
    if (mobileAdminLink) mobileAdminLink.style.display = 'none';
  }
}

function toggleUserDropdown() {
  var dd = document.getElementById('auth-dropdown');
  dd.classList.toggle('open');
}

// Close dropdown on outside click
document.addEventListener('click', function(e) {
  var dd = document.getElementById('auth-dropdown');
  var btn = document.getElementById('auth-avatar-btn');
  if (dd && btn && !btn.contains(e.target) && !dd.contains(e.target)) {
    dd.classList.remove('open');
  }
});

function hideAllAuthForms() {
  document.getElementById('auth-login-form').style.display = 'none';
  document.getElementById('auth-register-form').style.display = 'none';
  document.getElementById('auth-forgot-form').style.display = 'none';
  document.getElementById('auth-reset-form').style.display = 'none';
  document.getElementById('auth-error').style.display = 'none';
  document.getElementById('reg-error').style.display = 'none';
  var fe = document.getElementById('forgot-error'); if (fe) fe.style.display = 'none';
  var fs = document.getElementById('forgot-success'); if (fs) fs.style.display = 'none';
  var re = document.getElementById('reset-error'); if (re) re.style.display = 'none';
  var rs = document.getElementById('reset-success'); if (rs) rs.style.display = 'none';
}

function showLoginForm() {
  hideAllAuthForms();
  document.getElementById('auth-login-form').style.display = '';
  document.getElementById('auth-modal-title').textContent = 'Sign In';
}

function showRegisterForm() {
  hideAllAuthForms();
  document.getElementById('auth-register-form').style.display = '';
  document.getElementById('auth-modal-title').textContent = 'Create Account';
}

function showForgotForm() {
  hideAllAuthForms();
  document.getElementById('auth-forgot-form').style.display = '';
  document.getElementById('auth-modal-title').textContent = 'Forgot Password';
}

function showResetForm() {
  hideAllAuthForms();
  document.getElementById('auth-reset-form').style.display = '';
  document.getElementById('auth-modal-title').textContent = 'Reset Password';
}

function doForgotPassword() {
  var email = document.getElementById('forgot-email-input').value.trim();
  var errEl = document.getElementById('forgot-error');
  var succEl = document.getElementById('forgot-success');
  errEl.style.display = 'none';
  succEl.style.display = 'none';

  if (!email) { errEl.textContent = 'Please enter your email.'; errEl.style.display = ''; return; }

  var btn = document.getElementById('forgot-submit-btn-fe');
  btn.disabled = true; btn.textContent = 'Sending...';

  apiCall('/forgot-password', {
    method: 'POST',
    body: JSON.stringify({ email: email })
  }).then(function(res) {
    btn.disabled = false; btn.textContent = 'Send Reset Link';
    succEl.textContent = (res && res.message) || 'If that email exists, a reset link has been sent.';
    succEl.style.display = '';
  }).catch(function() {
    btn.disabled = false; btn.textContent = 'Send Reset Link';
    errEl.textContent = 'Something went wrong. Please try again.';
    errEl.style.display = '';
  });
}

function doResetPassword() {
  var params = new URLSearchParams(window.location.search);
  var token = params.get('token');
  var email = params.get('email');
  var password = document.getElementById('reset-password-input').value;
  var confirm = document.getElementById('reset-password-confirm').value;
  var errEl = document.getElementById('reset-error');
  var succEl = document.getElementById('reset-success');
  errEl.style.display = 'none';
  succEl.style.display = 'none';

  if (!password || !confirm) { errEl.textContent = 'Please fill in both fields.'; errEl.style.display = ''; return; }
  if (password.length < 8) { errEl.textContent = 'Password must be at least 8 characters.'; errEl.style.display = ''; return; }
  if (password !== confirm) { errEl.textContent = 'Passwords do not match.'; errEl.style.display = ''; return; }

  var btn = document.getElementById('reset-submit-btn');
  btn.disabled = true; btn.textContent = 'Resetting...';

  apiCall('/reset-password', {
    method: 'POST',
    body: JSON.stringify({ email: email, token: token, password: password, password_confirmation: confirm })
  }).then(function(res) {
    btn.disabled = false; btn.textContent = 'Reset Password';
    if (res && res.success) {
      succEl.textContent = res.message || 'Password reset successfully! You can now sign in.';
      succEl.style.display = '';
      window.history.replaceState({}, '', window.location.pathname);
      setTimeout(function() { showLoginForm(); }, 2000);
    } else {
      errEl.textContent = (res && res.message) || 'Invalid or expired reset token.';
      errEl.style.display = '';
    }
  }).catch(function() {
    btn.disabled = false; btn.textContent = 'Reset Password';
    errEl.textContent = 'Failed to reset password. The token may have expired.';
    errEl.style.display = '';
  });
}

function doLogin() {
  var email = document.getElementById('auth-email').value.trim();
  var password = document.getElementById('auth-password').value;
  var errEl = document.getElementById('auth-error');
  errEl.style.display = 'none';

  if (!email || !password) { errEl.textContent = 'Please enter email and password.'; errEl.style.display = ''; return; }

  var btn = document.getElementById('auth-submit-btn');
  btn.disabled = true; btn.textContent = 'Signing in...';

  apiCall('/login', {
    method: 'POST',
    body: JSON.stringify({ email: email, password: password })
  }).then(function(res) {
    btn.disabled = false; btn.textContent = 'Sign In';
    if (res && res.token) {
      authToken = res.token;
      authUser = res.user;
      localStorage.setItem('auth_token', authToken);
      localStorage.setItem('auth_user', JSON.stringify(authUser));
      updateAuthUI();
      closeModal('auth');
      showToast('Welcome back, ' + (authUser.name || '') + '!');
      document.getElementById('auth-email').value = '';
      document.getElementById('auth-password').value = '';
    } else {
      errEl.textContent = (res && res.message) || 'Invalid credentials.';
      errEl.style.display = '';
    }
  }).catch(function() {
    btn.disabled = false; btn.textContent = 'Sign In';
    errEl.textContent = 'Invalid credentials. Please try again.';
    errEl.style.display = '';
  });
}

function doRegister() {
  var name = document.getElementById('reg-name').value.trim();
  var email = document.getElementById('reg-email').value.trim();
  var password = document.getElementById('reg-password').value;
  var confirm = document.getElementById('reg-password-confirm').value;
  var errEl = document.getElementById('reg-error');
  errEl.style.display = 'none';

  if (!name || !email || !password || !confirm) { errEl.textContent = 'Please fill in all fields.'; errEl.style.display = ''; return; }
  if (password.length < 8) { errEl.textContent = 'Password must be at least 8 characters.'; errEl.style.display = ''; return; }
  if (password !== confirm) { errEl.textContent = 'Passwords do not match.'; errEl.style.display = ''; return; }

  // Collect custom profile fields
  var builtInKeys = ['phone', 'church_name', 'social_id', 'spiritual_background'];
  var payload = { name: name, email: email, password: password, password_confirmation: confirm };
  var customFieldsData = {};
  var missingRequired = [];
  customProfileFields.forEach(function(field) {
    var el = document.getElementById('reg-cf-' + field.key);
    var val = el ? el.value.trim() : '';
    if (field.required && !val) { missingRequired.push(field.label); }
    if (builtInKeys.indexOf(field.key) !== -1) {
      payload[field.key] = val || null;
    } else {
      if (val) customFieldsData[field.key] = val;
    }
  });
  if (missingRequired.length > 0) { errEl.textContent = 'Please fill in required fields: ' + missingRequired.join(', '); errEl.style.display = ''; return; }
  if (Object.keys(customFieldsData).length > 0) payload.custom_fields = customFieldsData;

  var btn = document.getElementById('reg-submit-btn');
  btn.disabled = true; btn.textContent = 'Creating account...';

  apiCall('/register', {
    method: 'POST',
    body: JSON.stringify(payload)
  }).then(function(res) {
    btn.disabled = false; btn.textContent = 'Create Account';
    if (res && res.token) {
      authToken = res.token;
      authUser = res.user;
      localStorage.setItem('auth_token', authToken);
      localStorage.setItem('auth_user', JSON.stringify(authUser));
      updateAuthUI();
      closeModal('auth');
      showToast('Welcome, ' + (authUser.name || '') + '! Account created successfully.');
      document.getElementById('reg-name').value = '';
      document.getElementById('reg-email').value = '';
      document.getElementById('reg-password').value = '';
      document.getElementById('reg-password-confirm').value = '';
    } else {
      errEl.textContent = (res && res.message) || 'Registration failed. Please try again.';
      errEl.style.display = '';
    }
  }).catch(function() {
    btn.disabled = false; btn.textContent = 'Create Account';
    errEl.textContent = 'Registration failed. Email may already be in use.';
    errEl.style.display = '';
  });
}

function doLogout() {
  var dd = document.getElementById('auth-dropdown');
  dd.classList.remove('open');

  if (authToken) {
    apiCall('/logout', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer ' + authToken,
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
      }
    });
  }

  authToken = null;
  authUser = null;
  localStorage.removeItem('auth_token');
  localStorage.removeItem('auth_user');
  updateAuthUI();
  showToast('Signed out successfully.');
}

// Handle social auth callback (token passed via URL params)
(function handleSocialAuthCallback() {
  var params = new URLSearchParams(window.location.search);
  var token = params.get('auth_token');
  var user = params.get('auth_user');
  var error = params.get('auth_error');

  if (token && user) {
    try {
      authToken = token;
      authUser = JSON.parse(user);
      localStorage.setItem('auth_token', authToken);
      localStorage.setItem('auth_user', JSON.stringify(authUser));
      // Clean URL
      window.history.replaceState({}, '', window.location.pathname);
      setTimeout(function() { showToast('Welcome, ' + (authUser.name || '') + '!'); }, 500);
    } catch(e) {}
  } else if (error) {
    window.history.replaceState({}, '', window.location.pathname);
    setTimeout(function() { showToast(error); }, 500);
  }
})();

// Handle reset-password URL (when user clicks email link)
(function handleResetPasswordUrl() {
  var params = new URLSearchParams(window.location.search);
  var token = params.get('token');
  var email = params.get('email');
  if (token && email && window.location.pathname === '/reset-password') {
    setTimeout(function() {
      openModal('auth');
      showResetForm();
    }, 500);
  }
})();

/* ===== PROFILE EDIT ===== */
function openProfileEdit() {
  var dd = document.getElementById('auth-dropdown');
  if (dd) dd.classList.remove('open');
  if (!authUser || !authToken) { openModal('auth'); return; }
  document.getElementById('profile-name').value = authUser.name || '';
  document.getElementById('profile-email').value = authUser.email || '';
  document.getElementById('profile-password').value = '';
  document.getElementById('profile-password-confirm').value = '';
  document.getElementById('profile-error').style.display = 'none';
  document.getElementById('profile-success').style.display = 'none';
  // Populate custom fields with current user data
  var builtInKeys = ['phone', 'church_name', 'social_id', 'spiritual_background'];
  customProfileFields.forEach(function(field) {
    var el = document.getElementById('profile-cf-' + field.key);
    if (el) {
      if (builtInKeys.indexOf(field.key) !== -1) {
        el.value = authUser[field.key] || '';
      } else {
        el.value = (authUser.custom_fields && authUser.custom_fields[field.key]) || '';
      }
    }
  });
  openModal('profile');
}

function doUpdateProfile() {
  var name = document.getElementById('profile-name').value.trim();
  var email = document.getElementById('profile-email').value.trim();
  var password = document.getElementById('profile-password').value;
  var confirm = document.getElementById('profile-password-confirm').value;
  var errEl = document.getElementById('profile-error');
  var succEl = document.getElementById('profile-success');
  errEl.style.display = 'none';
  succEl.style.display = 'none';

  if (!name || !email) { errEl.textContent = 'Name and email are required.'; errEl.style.display = ''; return; }
  if (password && password.length < 8) { errEl.textContent = 'Password must be at least 8 characters.'; errEl.style.display = ''; return; }
  if (password && password !== confirm) { errEl.textContent = 'Passwords do not match.'; errEl.style.display = ''; return; }

  var btn = document.getElementById('profile-submit-btn');
  btn.disabled = true; btn.textContent = 'Saving...';

  var payload = { name: name, email: email };
  if (password) { payload.password = password; payload.password_confirmation = confirm; }
  // Collect custom profile fields
  var builtInKeys = ['phone', 'church_name', 'social_id', 'spiritual_background'];
  var customFieldsData = {};
  customProfileFields.forEach(function(field) {
    var el = document.getElementById('profile-cf-' + field.key);
    var val = el ? el.value.trim() : '';
    if (builtInKeys.indexOf(field.key) !== -1) {
      payload[field.key] = val || null;
    } else {
      if (val) customFieldsData[field.key] = val;
    }
  });
  if (Object.keys(customFieldsData).length > 0) payload.custom_fields = customFieldsData;

  fetch(API + '/profile', {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': 'Bearer ' + authToken,
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
    },
    body: JSON.stringify(payload)
  }).then(function(r) { return r.json(); })
  .then(function(res) {
    btn.disabled = false; btn.textContent = 'Save Changes';
    if (res && res.user) {
      authUser = res.user;
      localStorage.setItem('auth_user', JSON.stringify(authUser));
      updateAuthUI();
      succEl.textContent = 'Profile updated successfully!';
      succEl.style.display = '';
      document.getElementById('profile-password').value = '';
      document.getElementById('profile-password-confirm').value = '';
      setTimeout(function() { closeModal('profile'); showToast('Profile updated!'); }, 1500);
    } else {
      errEl.textContent = (res && res.message) || 'Failed to update profile.';
      errEl.style.display = '';
    }
  }).catch(function() {
    btn.disabled = false; btn.textContent = 'Save Changes';
    errEl.textContent = 'Failed to update profile. Please try again.';
    errEl.style.display = '';
  });
}

// Enter key support for auth forms
document.addEventListener('keydown', function(e) {
  if (e.key === 'Enter') {
    var authModal = document.getElementById('modal-auth');
    if (authModal && authModal.classList.contains('open')) {
      var loginForm = document.getElementById('auth-login-form');
      var forgotForm = document.getElementById('auth-forgot-form');
      var resetForm = document.getElementById('auth-reset-form');
      if (loginForm && loginForm.style.display !== 'none') {
        doLogin();
      } else if (forgotForm && forgotForm.style.display !== 'none') {
        doForgotPassword();
      } else if (resetForm && resetForm.style.display !== 'none') {
        doResetPassword();
      } else {
        doRegister();
      }
    }
    var profileModal = document.getElementById('modal-profile');
    if (profileModal && profileModal.classList.contains('open')) {
      doUpdateProfile();
    }
  }
});

/* ===== PDF VIEWER ===== */
var pdfDoc = null;
var pdfPage = 0;
var pdfTotal = 0;
var pdfScale = 0;
var pdfBaseScale = 0;
var pdfRendering = false;

function openPdfViewer(url, title) {
  var overlay = document.getElementById('pdf-viewer');
  document.getElementById('pdf-viewer-title').textContent = title || 'Book Viewer';
  document.getElementById('pdf-page-info').textContent = 'Loading...';
  document.getElementById('pdf-loading').style.display = 'flex';
  document.getElementById('pdf-prev').disabled = true;
  document.getElementById('pdf-next').disabled = true;
  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';

  pdfDoc = null; pdfPage = 1; pdfTotal = 0; pdfScale = 0;

  if (typeof pdfjsLib !== 'undefined') {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
  }

  pdfjsLib.getDocument(url).promise.then(function(doc) {
    pdfDoc = doc;
    pdfTotal = doc.numPages;
    document.getElementById('pdf-loading').style.display = 'none';
    renderPdfPage(pdfPage);
  }).catch(function(err) {
    document.getElementById('pdf-loading').textContent = 'Failed to load PDF. Try the download button instead.';
    console.error('PDF load error:', err);
  });
}

function renderPdfPage(num, flipDir) {
  if (!pdfDoc || pdfRendering) return;
  pdfRendering = true;
  pdfPage = num;
  document.getElementById('pdf-page-info').textContent = num + ' / ' + pdfTotal;
  document.getElementById('pdf-prev').disabled = (num <= 1);
  document.getElementById('pdf-next').disabled = (num >= pdfTotal);

  pdfDoc.getPage(num).then(function(page) {
    var canvas = document.getElementById('pdf-canvas');
    var ctx = canvas.getContext('2d');
    var container = document.getElementById('pdf-canvas-container');

    if (pdfScale === 0) {
      // Auto-fit: calculate scale based on available space
      var bodyEl = document.querySelector('.pdf-viewer-body');
      var availW = bodyEl.clientWidth - 140;
      var availH = bodyEl.clientHeight - 40;
      var vp = page.getViewport({ scale: 1 });
      var scaleW = availW / vp.width;
      var scaleH = availH / vp.height;
      pdfScale = Math.min(scaleW, scaleH, 2);
      pdfBaseScale = pdfScale;
    }

    var viewport = page.getViewport({ scale: pdfScale });
    canvas.width = viewport.width;
    canvas.height = viewport.height;

    page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function() {
      pdfRendering = false;
      // Flip animation
      if (flipDir) {
        container.classList.add(flipDir === 'left' ? 'flip-left' : 'flip-right');
        setTimeout(function() { container.classList.remove('flip-left', 'flip-right'); }, 500);
      }
    });
  });
}

function pdfPrev() {
  if (pdfPage <= 1) return;
  renderPdfPage(pdfPage - 1, 'right');
}

function pdfNext() {
  if (pdfPage >= pdfTotal) return;
  renderPdfPage(pdfPage + 1, 'left');
}

function pdfZoom(delta, fit) {
  if (!pdfDoc) return;
  if (fit) {
    pdfScale = 0; // Will auto-fit
  } else {
    pdfScale = Math.max(0.5, Math.min(3, pdfScale + delta));
  }
  renderPdfPage(pdfPage);
}

function closePdfViewer() {
  document.getElementById('pdf-viewer').classList.remove('open');
  document.body.style.overflow = '';
  pdfDoc = null;
  var canvas = document.getElementById('pdf-canvas');
  var ctx = canvas.getContext('2d');
  ctx.clearRect(0, 0, canvas.width, canvas.height);
}

// Keyboard navigation for PDF viewer
document.addEventListener('keydown', function(e) {
  var overlay = document.getElementById('pdf-viewer');
  if (!overlay.classList.contains('open')) return;
  if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); pdfPrev(); }
  if (e.key === 'ArrowRight' || e.key === 'ArrowDown' || e.key === ' ') { e.preventDefault(); pdfNext(); }
  if (e.key === 'Escape') { closePdfViewer(); }
});

/* ===== HOMEPAGE WIDGET ENGINE ===== */
function loadWidgetConfig() {
  return apiCall('/settings/widgets/public').then(function(res) {
    if (res && res.data && res.data.widgets) {
      widgetConfig = res.data.widgets;
    }
    // Layout is applied after all content loads (in init block)
  });
}

function applyWidgetLayout() {
  var home = document.getElementById('page-home');
  if (!home || !widgetConfig) return;

  // Collect all home-widget elements
  var allWidgets = {};
  var els = home.querySelectorAll('.home-widget');
  for (var i = 0; i < els.length; i++) {
    var wid = els[i].id.replace('hw-', '');
    allWidgets[wid] = els[i];
  }

  // Reorder and show/hide based on config
  widgetConfig.forEach(function(w) {
    var el = allWidgets[w.id];
    if (!el) return;
    // Move to end of parent (preserves config order)
    home.appendChild(el);
    // Show/hide
    el.style.display = w.enabled ? '' : 'none';
  });

  // Load data for newly enabled home widgets
  widgetConfig.forEach(function(w) {
    if (!w.enabled) return;
    var count = (w.settings && w.settings.count) ? w.settings.count : 3;
    if (w.id === 'testimonies') loadHomeTestimonies(count);
    if (w.id === 'reviews') loadHomeReviews(count);
    if (w.id === 'ministries') loadHomeMinistries();
    if (w.id === 'galleries') loadHomeGalleries(count);
  });
}

function getWidgetSetting(widgetId, key, fallback) {
  if (!widgetConfig) return fallback;
  for (var i = 0; i < widgetConfig.length; i++) {
    if (widgetConfig[i].id === widgetId && widgetConfig[i].settings) {
      return widgetConfig[i].settings[key] !== undefined ? widgetConfig[i].settings[key] : fallback;
    }
  }
  return fallback;
}

function loadHomeTestimonies(count) {
  apiCall('/testimonies/approved').then(function(res) {
    var testimonies = [];
    if (res && res.data) {
      testimonies = res.data.data ? res.data.data : (Array.isArray(res.data) ? res.data : []);
    }
    var el = document.getElementById('home-testimonies');
    if (el) el.innerHTML = testimonies.slice(0, count || 3).map(testimonyCard).join('') || '<p class="loading">No testimonies yet.</p>';
  });
}
function loadHomeReviews(count) {
  apiCall('/reviews/approved').then(function(res) {
    var reviews = [];
    if (res && res.data) {
      reviews = res.data.data ? res.data.data : (Array.isArray(res.data) ? res.data : []);
    }
    var el = document.getElementById('home-reviews');
    if (el) el.innerHTML = reviews.slice(0, count || 3).map(reviewCard).join('') || '<p class="loading">No reviews yet.</p>';
  });
}
function loadHomeMinistries() {
  apiCall('/ministries').then(function(res) {
    var list = [];
    if (res && res.data) {
      list = res.data.data ? res.data.data : (Array.isArray(res.data) ? res.data : []);
    }
    var el = document.getElementById('home-ministries');
    if (el) el.innerHTML = list.slice(0, 6).map(function(m, i) {
      return '<div class="volunteer-card"><div class="volunteer-icon">' + MINISTRY_ICONS[i % MINISTRY_ICONS.length] + '</div>' +
        '<div class="volunteer-name">' + esc(m.name) + '</div></div>';
    }).join('') || '<p class="loading">No ministries.</p>';
  });
}
function loadHomeGalleries(count) {
  apiCall('/galleries').then(function(res) {
    var galleries = [];
    if (res && res.data) {
      galleries = res.data.data ? res.data.data : (Array.isArray(res.data) ? res.data : []);
    }
    var el = document.getElementById('home-galleries');
    if (el) el.innerHTML = galleries.slice(0, count || 6).map(function(g) {
      var thumb = g.cover_image ? '/storage/' + g.cover_image : (g.images && g.images[0] ? '/storage/' + g.images[0].path : '');
      return '<div class="card" style="padding:0;overflow:hidden">' +
        (thumb ? '<div style="height:140px;overflow:hidden"><img src="' + esc(thumb) + '" alt="' + esc(g.title) + '" style="width:100%;height:100%;object-fit:cover"></div>' : '') +
        '<div style="padding:0.8rem"><h3 class="card-title" style="font-size:0.9rem">' + esc(g.title) + '</h3></div></div>';
    }).join('') || '<p class="loading">No galleries.</p>';
  });
}
function submitHomeNewsletter() {
  var email = document.getElementById('home-newsletter-email').value;
  if (!email.trim()) { showToast('Please enter your email.'); return; }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showToast('Please enter a valid email.'); return; }
  apiCall('/newsletter/subscribe', {
    method: 'POST',
    body: JSON.stringify({ email: email })
  }).then(function(res) {
    if (res && res.success) {
      document.getElementById('home-newsletter-email').value = '';
      localStorage.setItem('newsletter-subscribed', '1');
      showToast('\uD83D\uDC8C Subscribed! Thank you for joining.');
    } else {
      showToast(res && res.message ? res.message : 'Failed. Try again.');
    }
  });
}
function submitHomeContact() {
  var n = document.getElementById('home-contact-name').value;
  var e = document.getElementById('home-contact-email').value;
  var s = document.getElementById('home-contact-subject').value;
  var m = document.getElementById('home-contact-message').value;
  if (!n.trim() || !e.trim() || !s.trim() || !m.trim()) { showToast('Please fill all fields.'); return; }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e)) { showToast('Please enter a valid email.'); return; }
  apiCall('/contact', {
    method: 'POST',
    body: JSON.stringify({ name: n, email: e, subject: s, message: m })
  }).then(function(res) {
    if (res && res.success) {
      document.getElementById('home-contact-name').value = '';
      document.getElementById('home-contact-email').value = '';
      document.getElementById('home-contact-subject').value = '';
      document.getElementById('home-contact-message').value = '';
      showToast('\u2709\uFE0F Message sent!');
    } else {
      showToast(res && res.message ? res.message : 'Failed. Try again.');
    }
  });
}

/* ===== BLOG ===== */
var allBlogPosts = [];
var blogCategories = [];
var blogCurrentPage = 1;
var blogTotalPages = 1;
var blogLayout = 'grid';
var blogCategoryFilter = 'All';
var blogSearchQuery = '';

function setBlogLayout(layout) {
  blogLayout = layout;
  document.getElementById('blog-grid-btn').classList.toggle('active', layout === 'grid');
  document.getElementById('blog-list-btn').classList.toggle('active', layout === 'list');
  var container = document.getElementById('blog-posts-container');
  container.className = layout === 'list' ? 'blog-list-container' : 'cards-grid';
  renderBlogPosts();
}

function loadBlogPage(page) {
  page = page || 1;
  blogCurrentPage = page;
  var url = '/posts/published?page=' + page + '&per_page=9';
  if (blogCategoryFilter && blogCategoryFilter !== 'All') url += '&category=' + encodeURIComponent(blogCategoryFilter);
  if (blogSearchQuery) url += '&search=' + encodeURIComponent(blogSearchQuery);

  apiCall(url).then(function(res) {
    if (res && res.data) {
      allBlogPosts = res.data;
      blogTotalPages = res.last_page || 1;
      blogCurrentPage = res.current_page || 1;
    } else {
      allBlogPosts = [];
    }
    renderBlogPosts();
    renderBlogPagination();
    loadBlogSidebar();
  });
}

function filterBlogPosts() {
  blogSearchQuery = document.getElementById('blog-search').value.trim();
  loadBlogPage(1);
}

function setBlogCategory(cat) {
  blogCategoryFilter = cat;
  document.querySelectorAll('#blog-category-filters .filter-btn').forEach(function(b) {
    b.classList.toggle('active', b.dataset.cat === cat);
  });
  loadBlogPage(1);
}

function renderBlogPosts() {
  var container = document.getElementById('blog-posts-container');
  if (!allBlogPosts || allBlogPosts.length === 0) {
    container.innerHTML = '<div class="blog-empty"><p>No posts found.</p></div>';
    return;
  }
  if (blogLayout === 'list') {
    container.innerHTML = allBlogPosts.map(blogListCard).join('');
  } else {
    container.innerHTML = allBlogPosts.map(blogGridCard).join('');
  }
}

function blogGridCard(p) {
  var img = p.featured_image ? '<div class="blog-card-img"><img src="/storage/' + esc(p.featured_image) + '" alt="' + esc(p.title) + '" loading="lazy"></div>' : '<div class="blog-card-img blog-card-img-placeholder"><span>&#128240;</span></div>';
  var cat = p.category ? '<span class="card-badge badge-worship">' + esc(p.category) + '</span>' : '';
  var tags = '';
  if (p.tags) {
    var tagArr = p.tags.split(',').slice(0, 3);
    tags = '<div class="blog-card-tags">' + tagArr.map(function(t) { return '<span class="blog-tag">' + esc(t.trim()) + '</span>'; }).join('') + '</div>';
  }
  return '<div class="card blog-card" onclick="viewBlogPost(\'' + esc(p.slug) + '\')">' +
    img + '<div class="blog-card-body">' + cat +
    '<h3 class="card-title blog-card-title">' + esc(p.title) + '</h3>' +
    '<p class="card-desc">' + esc(p.excerpt || (p.content || '').replace(/<[^>]*>/g, '').substring(0, 140)) + '...</p>' +
    '<div class="blog-card-footer">' +
    '<span class="card-meta">' + fmtDate(p.published_at || p.created_at) + '</span>' +
    '<span class="blog-read-more">Read More &#8594;</span>' +
    '</div>' + tags + '</div></div>';
}

function blogListCard(p) {
  var img = p.featured_image ? '<div class="blog-list-img"><img src="/storage/' + esc(p.featured_image) + '" alt="' + esc(p.title) + '" loading="lazy"></div>' : '<div class="blog-list-img blog-card-img-placeholder"><span>&#128240;</span></div>';
  var cat = p.category ? '<span class="card-badge badge-worship" style="margin-bottom:0.3rem">' + esc(p.category) + '</span>' : '';
  return '<div class="blog-list-item" onclick="viewBlogPost(\'' + esc(p.slug) + '\')">' +
    img + '<div class="blog-list-body">' + cat +
    '<h3 class="card-title blog-card-title">' + esc(p.title) + '</h3>' +
    '<p class="card-desc">' + esc(p.excerpt || (p.content || '').replace(/<[^>]*>/g, '').substring(0, 200)) + '...</p>' +
    '<div class="blog-card-footer">' +
    '<span class="card-meta">' + fmtDate(p.published_at || p.created_at) +
    (p.view_count ? ' &bull; ' + p.view_count + ' views' : '') + '</span>' +
    '<span class="blog-read-more">Read More &#8594;</span>' +
    '</div></div></div>';
}

function renderBlogPagination() {
  var el = document.getElementById('blog-pagination');
  if (blogTotalPages <= 1) { el.innerHTML = ''; return; }
  var html = '';
  if (blogCurrentPage > 1) {
    html += '<button class="blog-page-btn" onclick="loadBlogPage(' + (blogCurrentPage - 1) + ')">&#8592; Prev</button>';
  }
  for (var i = 1; i <= blogTotalPages; i++) {
    if (i === blogCurrentPage) {
      html += '<button class="blog-page-btn blog-page-active">' + i + '</button>';
    } else if (Math.abs(i - blogCurrentPage) <= 2 || i === 1 || i === blogTotalPages) {
      html += '<button class="blog-page-btn" onclick="loadBlogPage(' + i + ')">' + i + '</button>';
    } else if (Math.abs(i - blogCurrentPage) === 3) {
      html += '<span class="blog-page-dots">...</span>';
    }
  }
  if (blogCurrentPage < blogTotalPages) {
    html += '<button class="blog-page-btn" onclick="loadBlogPage(' + (blogCurrentPage + 1) + ')">Next &#8594;</button>';
  }
  el.innerHTML = html;
}

function loadBlogSidebar() {
  // Categories
  apiCall('/categories?type=post').then(function(res) {
    var cats = [];
    if (res && res.data) cats = Array.isArray(res.data) ? res.data : (res.data.data || []);
    blogCategories = cats;
    // Render category filters
    var filterHtml = '<button class="filter-btn ' + (blogCategoryFilter === 'All' ? 'active' : '') + '" data-cat="All" onclick="setBlogCategory(\'All\')">All</button>';
    cats.forEach(function(c) {
      filterHtml += '<button class="filter-btn ' + (blogCategoryFilter === c.name ? 'active' : '') + '" data-cat="' + esc(c.name) + '" onclick="setBlogCategory(\'' + esc(c.name) + '\')">' + esc(c.name) + '</button>';
    });
    document.getElementById('blog-category-filters').innerHTML = filterHtml;
    // Sidebar categories
    var sideHtml = '<div class="sidebar-cat-item' + (blogCategoryFilter === 'All' ? ' active' : '') + '" onclick="setBlogCategory(\'All\')"><span>All Posts</span></div>';
    cats.forEach(function(c) {
      sideHtml += '<div class="sidebar-cat-item' + (blogCategoryFilter === c.name ? ' active' : '') + '" onclick="setBlogCategory(\'' + esc(c.name) + '\')"><span>' + esc(c.name) + '</span></div>';
    });
    document.getElementById('sidebar-categories').innerHTML = sideHtml;
  });

  // Recent posts
  apiCall('/posts/published?per_page=5').then(function(res) {
    var posts = (res && res.data) ? res.data : [];
    var html = posts.map(function(p) {
      return '<div class="sidebar-recent-item" onclick="viewBlogPost(\'' + esc(p.slug) + '\')">' +
        (p.featured_image ? '<img src="/storage/' + esc(p.featured_image) + '" alt="' + esc(p.title) + '" class="sidebar-recent-img">' : '<div class="sidebar-recent-img sidebar-recent-placeholder">&#128240;</div>') +
        '<div><div class="sidebar-recent-title">' + esc(p.title) + '</div>' +
        '<div class="sidebar-recent-date">' + fmtDate(p.published_at || p.created_at) + '</div></div></div>';
    }).join('');
    document.getElementById('sidebar-recent').innerHTML = html || '<p style="font-size:0.85rem;color:var(--text-muted)">No posts yet.</p>';
    // Also populate detail sidebar if visible
    var ds = document.getElementById('blog-detail-sidebar');
    if (ds) {
      ds.innerHTML = '<div class="sidebar-widget"><h3 class="sidebar-title">Recent Posts</h3>' + (html || '<p style="font-size:0.85rem;color:var(--text-muted)">No posts yet.</p>') + '</div>';
    }
  });

  // Tags
  apiCall('/posts/published?per_page=50').then(function(res) {
    var posts = (res && res.data) ? res.data : [];
    var tagMap = {};
    posts.forEach(function(p) {
      if (p.tags) {
        p.tags.split(',').forEach(function(t) {
          t = t.trim();
          if (t) tagMap[t] = (tagMap[t] || 0) + 1;
        });
      }
    });
    var tags = Object.keys(tagMap).sort(function(a, b) { return tagMap[b] - tagMap[a]; }).slice(0, 20);
    document.getElementById('sidebar-tags').innerHTML = tags.map(function(t) {
      return '<span class="sidebar-tag" onclick="document.getElementById(\'blog-search\').value=\'' + esc(t) + '\'; filterBlogPosts();">' + esc(t) + '</span>';
    }).join('') || '<p style="font-size:0.85rem;color:var(--text-muted)">No tags yet.</p>';
  });
}

function viewBlogPost(slug) {
  // Navigate to SSR clean URL for blog posts
  window.location.href = '/blog/' + slug;
}

function copyPermalink(url) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(url).then(function() { showToast('Link copied to clipboard!'); });
  } else {
    var ta = document.createElement('textarea');
    ta.value = url; document.body.appendChild(ta); ta.select(); document.execCommand('copy');
    document.body.removeChild(ta); showToast('Link copied to clipboard!');
  }
}

/* ===== HOMEPAGE BLOG WIDGET (ENHANCED) ===== */
function renderHomePosts(posts, layout) {
  layout = layout || 'grid';
  var count = getWidgetSetting('posts', 'count', 3);
  var widgetLayout = getWidgetSetting('posts', 'layout', 'grid');
  var shown = posts.slice(0, count);
  if (shown.length === 0) return '';

  var header = '<div class="section-header"><h2 class="section-title">\uD83D\uDCF0 Latest Blog Posts</h2>' +
    '<button class="section-action" onclick="navigate(\'blog\')">View All</button></div>';

  if (widgetLayout === 'featured') {
    // Featured layout: first post large, rest small
    var first = shown[0];
    var rest = shown.slice(1);
    var html = header + '<div class="blog-featured-layout">' +
      '<div class="blog-featured-main" onclick="viewBlogPost(\'' + esc(first.slug) + '\')">' +
      (first.featured_image ? '<img src="/storage/' + esc(first.featured_image) + '" alt="' + esc(first.title) + '" class="blog-featured-img">' : '') +
      '<div class="blog-featured-overlay">' +
      (first.category ? '<span class="card-badge badge-worship">' + esc(first.category) + '</span>' : '') +
      '<h3 class="blog-featured-title">' + esc(first.title) + '</h3>' +
      '<p class="blog-featured-excerpt">' + esc(first.excerpt || '') + '</p>' +
      '<span class="card-meta">' + fmtDate(first.published_at || first.created_at) + '</span>' +
      '</div></div>';
    if (rest.length > 0) {
      html += '<div class="blog-featured-aside">' + rest.map(function(p) {
        return '<div class="blog-featured-small" onclick="viewBlogPost(\'' + esc(p.slug) + '\')">' +
          (p.featured_image ? '<img src="/storage/' + esc(p.featured_image) + '" alt="' + esc(p.title) + '">' : '<div class="blog-card-img-placeholder" style="width:80px;height:80px;min-width:80px"><span>&#128240;</span></div>') +
          '<div><h4>' + esc(p.title) + '</h4><span class="card-meta">' + fmtDate(p.published_at || p.created_at) + '</span></div></div>';
      }).join('') + '</div>';
    }
    return html + '</div>';
  } else if (widgetLayout === 'list') {
    return header + '<div class="blog-list-container">' + shown.map(blogListCard).join('') + '</div>';
  } else {
    // Default grid
    return header + '<div class="cards-grid">' + shown.map(blogGridCard).join('') + '</div>';
  }
}

/* ===== MOBILE THEME ===== */
var mobileThemeConfig = null;

function loadMobileTheme() {
  return apiCall('/mobile-theme').then(function(res) {
    if (res && res.success && res.data) {
      mobileThemeConfig = res.data;
      if (res.data.enabled && res.data.config) {
        applyMobileTheme(res.data.config);
      }
    }
  }).catch(function() {});
}

function applyMobileTheme(cfg) {
  var isMobile = window.innerWidth <= 900;
  var root = document.documentElement;

  // Font size
  if (cfg.font_size) {
    var sizes = { small: '14px', medium: '16px', large: '18px' };
    root.style.setProperty('--mobile-font-size', sizes[cfg.font_size] || '16px');
    if (isMobile) document.body.style.fontSize = sizes[cfg.font_size] || '16px';
  }

  // Card style
  if (cfg.card_style) {
    var cardStyles = {
      rounded: { radius: 'var(--radius-lg)', shadow: 'var(--shadow-sm)', border: '1px solid var(--border)' },
      flat: { radius: '4px', shadow: 'none', border: '1px solid var(--border)' },
      elevated: { radius: 'var(--radius-lg)', shadow: 'var(--shadow-md)', border: 'none' }
    };
    var cs = cardStyles[cfg.card_style] || cardStyles.rounded;
    root.style.setProperty('--card-radius', cs.radius);
    root.style.setProperty('--card-shadow', cs.shadow);
    root.style.setProperty('--card-border', cs.border);
    // Apply to existing cards
    document.querySelectorAll('.card, .sidebar-widget, .blog-card, .blog-list-item, .info-card, .sermon-card, .book-card').forEach(function(el) {
      el.style.borderRadius = cs.radius;
      el.style.boxShadow = cs.shadow;
      if (cs.border !== 'none') el.style.border = cs.border;
    });
  }

  // Header style
  if (cfg.header_style && isMobile) {
    var nav = document.querySelector('.nav-bar');
    if (nav) {
      if (cfg.header_style === 'large') {
        nav.style.padding = '0 1.5rem';
        var inner = nav.querySelector('.nav-inner');
        if (inner) inner.style.height = '72px';
      } else if (cfg.header_style === 'compact') {
        var inner2 = nav.querySelector('.nav-inner');
        if (inner2) inner2.style.height = '50px';
      }
    }
  }

  // Bottom navigation for mobile
  if (cfg.bottom_nav && isMobile) {
    buildBottomNav(cfg.bottom_nav);
  }

  // Quick actions
  if (cfg.quick_actions && isMobile) {
    buildQuickActions(cfg.quick_actions);
  }

  // Pull to refresh
  if (cfg.enable_pull_refresh && isMobile) {
    initPullToRefresh();
  }

  // Swipe navigation
  if (cfg.enable_swipe_nav && isMobile) {
    initSwipeNav();
  }

  // Splash screen config for PWA
  if (cfg.splash_screen) {
    root.style.setProperty('--splash-bg', cfg.splash_screen.background_color || '#4F46E5');
    root.style.setProperty('--splash-text', cfg.splash_screen.text_color || '#ffffff');
  }
}

function buildBottomNav(navItems) {
  var existing = document.getElementById('mobile-bottom-nav');
  if (existing) existing.remove();

  var enabledItems = navItems.filter(function(n) { return n.enabled; }).slice(0, 5);
  if (enabledItems.length === 0) return;

  var nav = document.createElement('nav');
  nav.id = 'mobile-bottom-nav';
  nav.className = 'mobile-bottom-nav';

  var routeMap = {
    '/': 'home', '/sermons': 'sermons', '/events': 'events', '/prayers': 'prayers',
    '/blog': 'blog', '/giving': 'giving', '/library': 'library', '/studies': 'studies',
    '/ministries': 'ministries', '/reviews': 'reviews', '/testimonies': 'testimonies',
    '/contact': 'contact', '/about': 'about', '/menu': ''
  };

  enabledItems.forEach(function(item) {
    var btn = document.createElement('button');
    btn.className = 'bottom-nav-item';
    var page = routeMap[item.route] || item.id;
    if (page === currentPage) btn.classList.add('active');
    btn.onclick = function() {
      if (item.route === '/menu') {
        toggleMobile();
      } else {
        navigate(page);
        updateBottomNav(page);
      }
    };
    btn.innerHTML = '<span class="bottom-nav-icon"><i class="fas ' + item.icon + '"></i></span>' +
      '<span class="bottom-nav-label">' + item.label + '</span>';
    nav.appendChild(btn);
  });

  document.body.appendChild(nav);
  // Add padding at bottom for content not to be hidden
  document.body.style.paddingBottom = '65px';
}

function updateBottomNav(activePage) {
  var items = document.querySelectorAll('.bottom-nav-item');
  items.forEach(function(item, i) {
    item.classList.remove('active');
  });
  // Re-check which is active
  var nav = document.getElementById('mobile-bottom-nav');
  if (!nav || !mobileThemeConfig || !mobileThemeConfig.config || !mobileThemeConfig.config.bottom_nav) return;
  var routeMap = {
    '/': 'home', '/sermons': 'sermons', '/events': 'events', '/prayers': 'prayers',
    '/blog': 'blog', '/giving': 'giving', '/library': 'library', '/studies': 'studies',
    '/ministries': 'ministries', '/reviews': 'reviews', '/testimonies': 'testimonies',
    '/contact': 'contact', '/about': 'about'
  };
  var enabled = mobileThemeConfig.config.bottom_nav.filter(function(n) { return n.enabled; });
  var buttons = nav.querySelectorAll('.bottom-nav-item');
  enabled.forEach(function(item, idx) {
    var page = routeMap[item.route] || item.id;
    if (page === activePage && buttons[idx]) buttons[idx].classList.add('active');
  });
}

function buildQuickActions(actions) {
  var enabled = actions.filter(function(a) { return a.enabled; });
  if (enabled.length === 0) return;

  var existing = document.getElementById('mobile-quick-actions');
  if (existing) existing.remove();

  var container = document.createElement('div');
  container.id = 'mobile-quick-actions';
  container.className = 'quick-actions-bar';

  var actionMap = {
    'donate': 'giving', 'prayer-request': 'prayers', 'contact': 'contact',
    'bible-studies': 'studies', 'sermons': 'sermons', 'events': 'events', 'blog': 'blog'
  };

  enabled.forEach(function(act) {
    var btn = document.createElement('button');
    btn.className = 'quick-action-btn';
    btn.innerHTML = '<i class="fas ' + act.icon + '"></i><span>' + act.label + '</span>';
    btn.onclick = function() {
      var page = actionMap[act.action] || act.action;
      if (PAGES.indexOf(page) !== -1) navigate(page);
    };
    container.appendChild(btn);
  });

  // Insert after hero section on home page
  var homeSection = document.getElementById('page-home');
  if (homeSection) {
    var hero = homeSection.querySelector('.hero-section');
    if (hero) hero.after(container);
    else homeSection.prepend(container);
  }
}

function initPullToRefresh() {
  var startY = 0;
  var pulling = false;
  document.addEventListener('touchstart', function(e) {
    if (window.scrollY === 0) {
      startY = e.touches[0].clientY;
      pulling = true;
    }
  }, { passive: true });
  document.addEventListener('touchmove', function(e) {
    if (!pulling) return;
    var diff = e.touches[0].clientY - startY;
    if (diff > 80) {
      pulling = false;
      window.location.reload();
    }
  }, { passive: true });
  document.addEventListener('touchend', function() { pulling = false; }, { passive: true });
}

function initSwipeNav() {
  var startX = 0;
  var startY = 0;
  document.addEventListener('touchstart', function(e) {
    startX = e.touches[0].clientX;
    startY = e.touches[0].clientY;
  }, { passive: true });
  document.addEventListener('touchend', function(e) {
    var diffX = e.changedTouches[0].clientX - startX;
    var diffY = e.changedTouches[0].clientY - startY;
    if (Math.abs(diffX) > 80 && Math.abs(diffX) > Math.abs(diffY) * 2) {
      var idx = PAGES.indexOf(currentPage);
      if (idx === -1) return;
      if (diffX > 0 && idx > 0) navigate(PAGES[idx - 1]);
      else if (diffX < 0 && idx < PAGES.length - 1) navigate(PAGES[idx + 1]);
    }
  }, { passive: true });
}

// Load PWA config and update meta tags
function loadPwaConfig() {
  return apiCall('/pwa-config').then(function(res) {
    if (res && res.success && res.data) {
      var d = res.data;
      // Update theme-color meta tag
      var themeMeta = document.querySelector('meta[name="theme-color"]');
      if (themeMeta && d.pwa_theme_color) themeMeta.content = d.pwa_theme_color;
      // Update apple meta tags
      var appleName = document.querySelector('meta[name="apple-mobile-web-app-title"]');
      if (appleName && d.pwa_name) appleName.content = d.pwa_name;
    }
  }).catch(function() {});
}

/* ===== CHURCH DIRECTORY & DETAIL ===== */
var churchDirPage = 1;
var churchDirLoading = false;

function loadChurchDirectory(page) {
  page = page || 1;
  churchDirPage = page;
  var container = document.getElementById('churches-list');
  var loadMore = document.getElementById('churches-load-more');
  if (page === 1) container.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-secondary)">Loading churches...</div>';
  var searchVal = (document.getElementById('church-search-input') || {}).value || '';
  var url = '/churches?page=' + page + '&per_page=12';
  if (searchVal) url += '&search=' + encodeURIComponent(searchVal);
  churchDirLoading = true;
  apiCall(url).then(function(res) {
    churchDirLoading = false;
    if (!res || !res.data) { container.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-secondary)">No churches found.</div>'; return; }
    if (page === 1) container.innerHTML = '';
    res.data.forEach(function(c) {
      container.innerHTML += churchDirectoryCard(c);
    });
    if (res.current_page < res.last_page) {
      loadMore.innerHTML = '<button class="btn-primary" onclick="loadChurchDirectory(' + (page + 1) + ')">Load More</button>';
    } else {
      loadMore.innerHTML = '';
    }
  });
}

function searchChurches() { loadChurchDirectory(1); }

function churchDirectoryCard(c) {
  var color = c.primary_color || '#4F46E5';
  var img = c.cover_photo_url
    ? '<div class="church-card-cover" style="background-image:url(\'' + esc(c.cover_photo_url) + '\')"></div>'
    : '<div class="church-card-cover church-card-cover-default" style="background:linear-gradient(135deg,' + esc(color) + ',#1e1b4b)"><span style="font-size:2.5rem">&#9962;</span></div>';
  var logo = c.logo_url
    ? '<img src="' + esc(c.logo_url) + '" alt="" class="church-card-logo">'
    : '';
  return '<div class="card church-card" onclick="viewChurchPage(\'' + esc(c.slug) + '\')" style="cursor:pointer">' +
    img + '<div class="church-card-body">' + logo +
    '<h3 class="church-card-name">' + esc(c.name) + '</h3>' +
    (c.denomination ? '<div class="church-card-denom">' + esc(c.denomination) + '</div>' : '') +
    (c.city ? '<div class="church-card-loc"><span>&#128205;</span> ' + esc(c.city) + (c.state ? ', ' + esc(c.state) : '') + '</div>' : '') +
    (c.short_description ? '<p class="church-card-desc">' + esc(c.short_description).substring(0, 120) + (c.short_description.length > 120 ? '...' : '') + '</p>' : '') +
    '</div></div>';
}

function viewChurchPage(slug) {
  // Hide all pages
  PAGES.forEach(function(p) {
    document.getElementById('page-' + p).classList.remove('active');
  });
  var blogDetail = document.getElementById('page-blog-detail');
  if (blogDetail) blogDetail.classList.remove('active');
  var churchesPage = document.getElementById('page-churches');
  if (churchesPage) churchesPage.classList.remove('active');

  var container = document.getElementById('church-detail-content');
  container.innerHTML = '<div style="text-align:center;padding:3rem;color:var(--text-secondary)">Loading church...</div>';
  document.getElementById('page-church-detail').classList.add('active');
  history.pushState({ page: 'church-detail', slug: slug }, '', '/church/' + slug);
  window.scrollTo({ top: 0, behavior: 'smooth' });

  // Track view
  apiCall('/churches/' + slug + '/view', { method: 'POST' });

  apiCall('/churches/' + slug).then(function(res) {
    if (!res || !res.data) {
      container.innerHTML = '<div style="text-align:center;padding:3rem;color:var(--text-secondary)">Church not found.</div>';
      return;
    }
    var c = res.data;
    var color = c.primary_color || '#4F46E5';
    var html = '';

    // Cover
    if (c.cover_photo_url) {
      html += '<div class="church-page-cover" style="background-image:url(\'' + esc(c.cover_photo_url) + '\')"><div class="church-page-cover-overlay" style="background:linear-gradient(transparent,rgba(0,0,0,0.7))"></div></div>';
    } else {
      html += '<div class="church-page-cover church-page-cover-default" style="background:linear-gradient(135deg,' + esc(color) + ',#1e1b4b)"></div>';
    }

    // Header
    html += '<div class="church-page-header">';
    if (c.logo_url) {
      html += '<img src="' + esc(c.logo_url) + '" alt="" class="church-page-logo">';
    }
    html += '<div>';
    html += '<h1 class="church-page-name" style="color:' + esc(color) + '">' + esc(c.name) + '</h1>';
    if (c.denomination) html += '<div class="church-page-denom">' + esc(c.denomination) + (c.year_founded ? ' &bull; Est. ' + c.year_founded : '') + '</div>';
    if (c.city) html += '<div class="church-page-loc">&#128205; ' + esc(c.address ? c.address + ', ' : '') + esc(c.city) + (c.state ? ', ' + esc(c.state) : '') + (c.zip_code ? ' ' + esc(c.zip_code) : '') + '</div>';
    html += '</div></div>';

    // Content grid
    html += '<div class="church-page-grid">';

    // Left column
    html += '<div class="church-page-main">';

    // Short description
    if (c.short_description) {
      html += '<div class="church-page-section"><p style="font-size:1.1rem;color:var(--text-secondary);line-height:1.7">' + esc(c.short_description) + '</p></div>';
    }

    // Mission / Vision
    if (c.mission_statement) {
      html += '<div class="church-page-section"><h3 style="color:' + esc(color) + '">Our Mission</h3><p>' + esc(c.mission_statement) + '</p></div>';
    }
    if (c.vision_statement) {
      html += '<div class="church-page-section"><h3 style="color:' + esc(color) + '">Our Vision</h3><p>' + esc(c.vision_statement) + '</p></div>';
    }

    // History
    if (c.history) {
      html += '<div class="church-page-section"><h3 style="color:' + esc(color) + '">Our Story</h3><div class="church-page-history">' + c.history + '</div></div>';
    }

    // Documents
    if (c.documents && c.documents.length > 0) {
      html += '<div class="church-page-section"><h3 style="color:' + esc(color) + '">Documents</h3><div class="church-page-docs">';
      c.documents.forEach(function(doc) {
        html += '<a href="/storage/' + esc(doc.file_path) + '" target="_blank" class="church-page-doc"><span>&#128196;</span> ' + esc(doc.name) + '</a>';
      });
      html += '</div></div>';
    }

    html += '</div>'; // end main

    // Right sidebar
    html += '<div class="church-page-sidebar">';

    // Service hours
    if (c.service_hours && c.service_hours.length > 0) {
      html += '<div class="church-sidebar-card"><h4 style="color:' + esc(color) + '">&#128337; Service Times</h4>';
      c.service_hours.forEach(function(sh) {
        html += '<div class="church-service-row"><strong>' + esc(sh.day) + '</strong><span>' + esc(sh.time) + '</span></div>';
        if (sh.label) html += '<div class="church-service-label">' + esc(sh.label) + '</div>';
      });
      html += '</div>';
    }

    // Contact
    html += '<div class="church-sidebar-card"><h4 style="color:' + esc(color) + '">&#128222; Contact</h4>';
    if (c.phone) html += '<div class="church-contact-row"><span>&#128222;</span> ' + esc(c.phone) + '</div>';
    if (c.email) html += '<div class="church-contact-row"><span>&#9993;</span> <a href="mailto:' + esc(c.email) + '">' + esc(c.email) + '</a></div>';
    if (c.website) html += '<div class="church-contact-row"><span>&#127760;</span> <a href="' + esc(c.website) + '" target="_blank">' + esc(c.website.replace(/^https?:\/\//, '')) + '</a></div>';
    html += '</div>';

    // Social
    var socials = [];
    if (c.facebook_url) socials.push('<a href="' + esc(c.facebook_url) + '" target="_blank" class="church-social-link" style="background:' + esc(color) + '">Facebook</a>');
    if (c.instagram_url) socials.push('<a href="' + esc(c.instagram_url) + '" target="_blank" class="church-social-link" style="background:' + esc(color) + '">Instagram</a>');
    if (c.youtube_url) socials.push('<a href="' + esc(c.youtube_url) + '" target="_blank" class="church-social-link" style="background:' + esc(color) + '">YouTube</a>');
    if (c.twitter_url) socials.push('<a href="' + esc(c.twitter_url) + '" target="_blank" class="church-social-link" style="background:' + esc(color) + '">Twitter</a>');
    if (c.tiktok_url) socials.push('<a href="' + esc(c.tiktok_url) + '" target="_blank" class="church-social-link" style="background:' + esc(color) + '">TikTok</a>');
    if (socials.length > 0) {
      html += '<div class="church-sidebar-card"><h4 style="color:' + esc(color) + '">Follow Us</h4><div class="church-social-links">' + socials.join('') + '</div></div>';
    }

    html += '</div>'; // end sidebar
    html += '</div>'; // end grid

    // Back button
    html += '<div style="margin-top:2rem"><button class="blog-back-btn" onclick="navigate(\'churches\')">&#8592; Back to Directory</button></div>';

    container.innerHTML = html;
  });
}

// Re-apply bottom nav highlight when navigating
var _origNavigate = navigate;
navigate = function(page, opts) {
  _origNavigate(page, opts);
  if (window.innerWidth <= 900) updateBottomNav(page);
};

/* ===== INIT ===== */
buildNav();
buildStarInput();
buildGiving();
updateAuthUI();
Promise.allSettled([
  loadWidgetConfig(),
  loadVerse(), loadBlessing(), loadAnnouncements(), loadPosts(), loadPrayers(), loadEvents(),
  loadBooks(), loadStudies(), loadSermons(), loadReviews(), loadTestimonies(), loadChurchSettings(), loadMinistries(),
  loadMobileTheme(), loadPwaConfig(), loadFrontendCategories(), loadFrontendMenus()
]).then(function() {
  // Apply widget layout after all content is loaded to avoid race conditions
  applyWidgetLayout();
  // Handle clean URL routing on initial load
  handleRoute();
});
document.querySelectorAll('.modal-overlay').forEach(function(m) {
  m.addEventListener('click', function(e) { if (e.target === m) m.classList.remove('open'); });
});
</script>
<?php /**PATH /home/alphaome/nkhoj.com/resources/views/partials/frontend-js.blade.php ENDPATH**/ ?>