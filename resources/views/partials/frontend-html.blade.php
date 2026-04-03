<!-- NAV -->
<nav class="nav-bar">
  <div class="nav-inner">
    <div class="nav-brand" onclick="navigate('home')">
      <div class="nav-brand-icon">&#10013;</div>
      <span class="nav-brand-text" id="nav-church-name">{{ config('app.name', 'Grace Community Church') }}</span>
    </div>
    <ul class="nav-links" id="nav-links"></ul>
    <div class="nav-right">
      <button class="theme-toggle" id="theme-toggle" onclick="toggleTheme()" title="Toggle dark/light mode">&#9790;</button>
      <div class="auth-nav" id="auth-nav">
        <button class="auth-nav-btn" id="auth-login-btn" onclick="openModal('auth')" title="Sign In">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </button>
        <div class="auth-user-menu" id="auth-user-menu" style="display:none">
          <button class="auth-avatar-btn" id="auth-avatar-btn" onclick="toggleUserDropdown()">
            <span class="auth-avatar-text" id="auth-avatar-text"></span>
          </button>
          <div class="auth-dropdown" id="auth-dropdown">
            <div class="auth-dropdown-header">
              <div class="auth-dropdown-name" id="auth-dropdown-name"></div>
              <div class="auth-dropdown-email" id="auth-dropdown-email"></div>
            </div>
            <div class="auth-dropdown-divider"></div>
            <a class="auth-dropdown-item" id="admin-panel-link" href="/admin" style="display:none;text-decoration:none;color:inherit;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline;vertical-align:middle;margin-right:6px"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>Admin Panel
            </a>
            <button class="auth-dropdown-item" onclick="openProfileEdit()">Edit Profile</button>
            <button class="auth-dropdown-item" onclick="doLogout()">Sign Out</button>
          </div>
        </div>
      </div>
      <button class="nav-mobile-btn" id="hamburger-btn" onclick="toggleMobile()">&#9776;</button>
    </div>
  </div>
</nav>
<!-- MOBILE MENU (slide-in) -->
<div class="mobile-menu" id="mobile-menu">
  <div class="mobile-header">
    <span class="mobile-header-brand" id="mobile-brand-name">{{ config('app.name', 'Grace Community Church') }}</span>
    <button class="mobile-close" onclick="toggleMobile()">&#10005;</button>
  </div>
  <div class="mobile-nav-list" id="mobile-links"></div>
  <div class="mobile-auth-section" id="mobile-auth-section">
    <button class="mobile-nav-item" id="mobile-login-btn" onclick="closeMobile(); openModal('auth')">
      <span class="mobile-nav-icon">&#128100;</span>Sign In
    </button>
    <div id="mobile-user-info" style="display:none">
      <div class="mobile-nav-item" style="cursor:default;color:var(--gold)">
        <span class="mobile-nav-icon">&#128100;</span><span id="mobile-user-name"></span>
      </div>
      <a class="mobile-nav-item" id="mobile-admin-link" href="/admin" style="display:none;text-decoration:none;color:inherit;">
        <span class="mobile-nav-icon">&#9881;</span>Admin Panel
      </a>
      <button class="mobile-nav-item" onclick="openProfileEdit(); closeMobile()">
        <span class="mobile-nav-icon">&#9998;</span>Edit Profile
      </button>
      <button class="mobile-nav-item" onclick="doLogout(); closeMobile()">
        <span class="mobile-nav-icon">&#128682;</span>Sign Out
      </button>
    </div>
  </div>
</div>
<!-- MAIN -->
<main class="main-content">
  <!-- HOME (dynamic sections rendered by JS from widget_config) -->
  <div class="page-section active" id="page-home">
    <!-- Container: JS will reorder/show/hide these based on widget_config -->
    <div id="hw-announcements" class="home-widget">
      <div class="ticker">
        <span class="ticker-label">Announcements</span>
        <span class="ticker-text" id="ticker-text">Welcome to our church! Check back for announcements.</span>
      </div>
    </div>
    <div id="hw-verse" class="home-widget">
      <section class="hero-section">
        <div class="hero-label">Verse of the Day</div>
        <p class="verse-text" id="verse-text">"Loading..."</p>
        <span class="verse-ref" id="verse-ref"></span>
      </section>
    </div>
    <div id="hw-blessing" class="home-widget">
      <section class="blessing-card">
        <h2 class="blessing-title" id="blessing-title">Today's Blessing</h2>
        <p class="blessing-text" id="blessing-text">Loading...</p>
        <p class="blessing-author" id="blessing-author"></p>
      </section>
    </div>
    <div id="hw-posts" class="home-widget">
      <div id="home-posts"></div>
    </div>
    <div id="hw-prayers" class="home-widget">
      <div class="section-header">
        <h2 class="section-title">&#128591; Prayer Requests</h2>
        <button class="section-action" onclick="openModal('prayer')">+ Submit Prayer</button>
      </div>
      <div class="cards-grid" id="home-prayers"></div>
    </div>
    <div id="hw-events" class="home-widget">
      <div class="section-header">
        <h2 class="section-title">&#128197; Upcoming Events</h2>
        <button class="section-action" onclick="navigate('events')">View All</button>
      </div>
      <div class="cards-grid" id="home-events"></div>
    </div>
    <div id="hw-sermon" class="home-widget">
      <div id="home-sermon"></div>
    </div>
    <div id="hw-testimonies" class="home-widget" style="display:none">
      <div class="section-header">
        <h2 class="section-title">&#10013; Testimonies</h2>
        <button class="section-action" onclick="navigate('testimonies')">View All</button>
      </div>
      <div class="cards-grid" id="home-testimonies"></div>
    </div>
    <div id="hw-reviews" class="home-widget" style="display:none">
      <div class="section-header">
        <h2 class="section-title">&#11088; Reviews</h2>
        <button class="section-action" onclick="navigate('reviews')">View All</button>
      </div>
      <div class="cards-grid" id="home-reviews"></div>
    </div>
    <div id="hw-ministries" class="home-widget" style="display:none">
      <div class="section-header">
        <h2 class="section-title">&#129309; Ministries</h2>
        <button class="section-action" onclick="navigate('ministries')">View All</button>
      </div>
      <div class="cards-grid" id="home-ministries"></div>
    </div>
    <div id="hw-galleries" class="home-widget" style="display:none">
      <div class="section-header">
        <h2 class="section-title">&#128248; Gallery</h2>
      </div>
      <div class="cards-grid" id="home-galleries"></div>
    </div>
    <div id="hw-newsletter" class="home-widget" style="display:none">
      <section class="blessing-card" style="text-align:center">
        <h2 class="blessing-title" style="font-size:1.4rem">&#128236; Stay Connected</h2>
        <p class="blessing-text" style="font-size:0.95rem;margin-bottom:1rem">Subscribe to our newsletter for weekly updates, devotionals, and church news.</p>
        <div style="display:flex;gap:0.5rem;max-width:400px;margin:0 auto">
          <input class="form-input" id="home-newsletter-email" type="email" placeholder="Your email address" style="flex:1">
          <button class="btn-primary" onclick="submitHomeNewsletter()" style="white-space:nowrap">Subscribe</button>
        </div>
      </section>
    </div>
    <div id="hw-contact" class="home-widget" style="display:none">
      <section class="blessing-card">
        <h2 class="blessing-title" style="font-size:1.4rem">&#9993;&#65039; Get in Touch</h2>
        <p class="blessing-text" style="font-size:0.95rem;margin-bottom:1rem">Have a question? Send us a message.</p>
        <div style="max-width:500px;margin:0 auto">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:0.5rem">
            <input class="form-input" id="home-contact-name" placeholder="Your name">
            <input class="form-input" id="home-contact-email" type="email" placeholder="Your email">
          </div>
          <input class="form-input" id="home-contact-subject" placeholder="Subject" style="margin-bottom:0.5rem">
          <textarea class="form-textarea" id="home-contact-message" placeholder="Your message..." rows="3" style="margin-bottom:0.5rem"></textarea>
          <button class="btn-primary" onclick="submitHomeContact()">Send Message</button>
        </div>
      </section>
    </div>
  </div>
  <!-- EVENTS -->
  <div class="page-section" id="page-events">
    <div class="section-header"><h2 class="section-title">&#128197; Church Events</h2></div>
    <div class="cards-grid" id="all-events"></div>
  </div>
  <!-- BLOG -->
  <div class="page-section" id="page-blog">
    <div class="section-header">
      <h2 class="section-title">&#128240; Blog</h2>
      <div class="blog-layout-toggle">
        <button class="blog-layout-btn active" id="blog-grid-btn" onclick="setBlogLayout('grid')" title="Grid view">&#9638;&#9638;</button>
        <button class="blog-layout-btn" id="blog-list-btn" onclick="setBlogLayout('list')" title="List view">&#9776;</button>
      </div>
    </div>
    <div class="blog-search-row">
      <div class="search-bar" style="flex:1">
        <span>&#128269;</span>
        <input placeholder="Search posts..." id="blog-search" oninput="filterBlogPosts()">
      </div>
    </div>
    <div class="blog-main-layout">
      <div class="blog-content-area">
        <div class="filter-btns" id="blog-category-filters"></div>
        <div class="cards-grid" id="blog-posts-container"></div>
        <div class="blog-pagination" id="blog-pagination"></div>
      </div>
      <aside class="blog-sidebar" id="blog-sidebar">
        <div class="sidebar-widget">
          <h3 class="sidebar-title">Categories</h3>
          <div class="sidebar-categories" id="sidebar-categories"></div>
        </div>
        <div class="sidebar-widget">
          <h3 class="sidebar-title">Recent Posts</h3>
          <div class="sidebar-recent" id="sidebar-recent"></div>
        </div>
        <div class="sidebar-widget">
          <h3 class="sidebar-title">Tags</h3>
          <div class="sidebar-tags" id="sidebar-tags"></div>
        </div>
      </aside>
    </div>
  </div>
  <!-- BLOG POST DETAIL -->
  <div class="page-section" id="page-blog-detail">
    <div class="blog-detail-container">
      <button class="blog-back-btn" onclick="navigate('blog')">&#8592; Back to Blog</button>
      <article class="blog-article" id="blog-article"></article>
      <aside class="blog-sidebar blog-detail-sidebar" id="blog-detail-sidebar"></aside>
    </div>
  </div>
  <!-- PRAYERS -->
  <div class="page-section" id="page-prayers">
    <div class="section-header">
      <h2 class="section-title">&#128591; Prayer Wall</h2>
      <button class="section-action" onclick="openModal('prayer')">+ Submit Prayer</button>
    </div>
    <p style="color:var(--text-secondary);margin-bottom:1.2rem;font-style:italic">"Pray for one another, that you may be healed." &mdash; James 5:16</p>
    <div class="cards-grid" id="all-prayers"></div>
  </div>
  <!-- LIBRARY -->
  <div class="page-section" id="page-library">
    <div class="section-header"><h2 class="section-title">&#128218; Book Library</h2></div>
    <div class="search-bar">
      <span>&#128269;</span>
      <input placeholder="Search books by title or author..." id="book-search" oninput="filterBooks()">
    </div>
    <div class="filter-btns" id="book-filters"></div>
    <div class="cards-grid" id="all-books"></div>
  </div>
  <!-- BIBLE STUDY -->
  <div class="page-section" id="page-studies">
    <div class="section-header"><h2 class="section-title">&#128214; Bible Study Hub</h2></div>
    <p style="color:var(--text-secondary);margin-bottom:1.2rem">Join a Bible study group and deepen your understanding of God's Word together.</p>
    <div class="cards-grid" id="all-studies"></div>
  </div>
  <!-- SERMONS -->
  <div class="page-section" id="page-sermons">
    <div class="section-header"><h2 class="section-title">&#127897;&#65039; Sermon Archive</h2></div>
    <div id="all-sermons"></div>
  </div>
  <!-- GIVING -->
  <div class="page-section" id="page-giving">
    <div class="section-header"><h2 class="section-title">&#128155; Online Giving</h2></div>
    <div class="giving-card">
      <h3 style="font-family:var(--font-display);font-size:1.7rem;color:var(--cream);margin-bottom:0.4rem">Give Generously</h3>
      <p style="color:var(--text-secondary);max-width:480px;margin:0 auto 1.2rem">"Each of you should give what you have decided in your heart to give, not reluctantly or under compulsion, for God loves a cheerful giver." &mdash; 2 Corinthians 9:7</p>
      <div class="giving-amounts" id="giving-amounts"></div>
      <div style="margin:1rem 0"><input class="form-input" placeholder="Or enter custom amount..." id="custom-amount" style="max-width:280px;text-align:center;margin:0 auto;display:block"></div>
      <button class="btn-primary" style="max-width:280px;margin:0 auto" onclick="submitDonation()">&#128155; Give Now</button>
    </div>
  </div>
  <!-- MINISTRIES -->
  <div class="page-section" id="page-ministries">
    <div class="section-header"><h2 class="section-title">&#129309; Ministries</h2></div>
    <p style="color:var(--text-secondary);margin-bottom:1.2rem">Use your God-given gifts to serve our community.</p>
    <div class="volunteer-grid" id="all-ministries"></div>
  </div>
  <!-- REVIEWS -->
  <div class="page-section" id="page-reviews">
    <div class="section-header">
      <h2 class="section-title">&#11088; Church Reviews</h2>
      <button class="section-action" onclick="openModal('review')">+ Write Review</button>
    </div>
    <div style="margin-bottom:1.5rem;display:flex;align-items:center;gap:1rem">
      <span class="avg-rating" id="avg-rating">0</span>
      <div>
        <div id="avg-stars"></div>
        <span style="font-size:0.82rem;color:var(--text-muted)" id="total-reviews">0 reviews</span>
      </div>
    </div>
    <div class="cards-grid" id="all-reviews"></div>
  </div>
  <!-- TESTIMONIES -->
  <div class="page-section" id="page-testimonies">
    <div class="section-header">
      <h2 class="section-title">&#10013; Testimonies</h2>
      <button class="section-action" onclick="openModal('testimony')">+ Share Your Testimony</button>
    </div>
    <p style="color:var(--text-secondary);margin-bottom:1.2rem;font-style:italic">"They triumphed over him by the blood of the Lamb and by the word of their testimony." &mdash; Revelation 12:11</p>
    <div class="cards-grid" id="all-testimonies"></div>
  </div>
  <!-- CONTACT -->
  <div class="page-section" id="page-contact">
    <div class="section-header"><h2 class="section-title">&#9993;&#65039; Contact Us</h2></div>
    <p style="color:var(--text-secondary);margin-bottom:1.5rem;font-style:italic">"Let us therefore come boldly unto the throne of grace." &mdash; Hebrews 4:16</p>
    <div class="contact-layout">
      <div class="contact-form-card">
        <h3 style="font-family:var(--font-display);font-size:1.2rem;color:var(--cream);margin-bottom:1rem">Send Us a Message</h3>
        <div class="form-group">
          <label class="form-label">Your Name *</label>
          <input class="form-input" id="contact-name" placeholder="Enter your full name">
        </div>
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input class="form-input" id="contact-email" type="email" placeholder="Enter your email">
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input class="form-input" id="contact-phone" type="tel" placeholder="Enter your phone number (optional)">
        </div>
        <div class="form-group">
          <label class="form-label">Subject *</label>
          <input class="form-input" id="contact-subject" placeholder="What is this about?">
        </div>
        <div class="form-group">
          <label class="form-label">Message *</label>
          <textarea class="form-textarea" id="contact-message" rows="5" placeholder="Write your message here..."></textarea>
        </div>
        <button class="btn-primary" onclick="submitContact()">&#9993;&#65039; Send Message</button>
      </div>
      <div class="contact-info-card">
        <div class="contact-info-item">
          <div class="contact-info-icon">&#128205;</div>
          <div>
            <h4 style="font-family:var(--font-display);font-size:0.95rem;color:var(--cream);margin-bottom:0.2rem">Address</h4>
            <p style="font-size:0.88rem;color:var(--text-secondary)" id="contact-address">Loading...</p>
          </div>
        </div>
        <div class="contact-info-item">
          <div class="contact-info-icon">&#128222;</div>
          <div>
            <h4 style="font-family:var(--font-display);font-size:0.95rem;color:var(--cream);margin-bottom:0.2rem">Phone</h4>
            <p style="font-size:0.88rem;color:var(--text-secondary)" id="contact-phone-info">Loading...</p>
          </div>
        </div>
        <div class="contact-info-item">
          <div class="contact-info-icon">&#9993;&#65039;</div>
          <div>
            <h4 style="font-family:var(--font-display);font-size:0.95rem;color:var(--cream);margin-bottom:0.2rem">Email</h4>
            <p style="font-size:0.88rem;color:var(--text-secondary)" id="contact-email-info">Loading...</p>
          </div>
        </div>
        <div class="contact-info-item">
          <div class="contact-info-icon">&#9962;</div>
          <div>
            <h4 style="font-family:var(--font-display);font-size:0.95rem;color:var(--cream);margin-bottom:0.2rem">Service Times</h4>
            <p style="font-size:0.88rem;color:var(--text-secondary)" id="contact-service-times">Loading...</p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- CHURCHES DIRECTORY -->
  <div class="page-section" id="page-churches">
    <div class="section-header">
      <h2 class="section-title">&#9962; Church Directory</h2>
    </div>
    <div class="church-dir-filters" style="margin-bottom:1.5rem;display:flex;gap:0.8rem;flex-wrap:wrap">
      <input type="text" class="form-input" id="church-search-input" placeholder="Search churches..." style="flex:1;min-width:200px">
      <button class="btn-primary" onclick="searchChurches()" style="white-space:nowrap">Search</button>
    </div>
    <div class="cards-grid" id="churches-list"></div>
    <div id="churches-load-more" style="text-align:center;margin-top:1.5rem"></div>
  </div>

  <!-- CHURCH DETAIL -->
  <div class="page-section" id="page-church-detail">
    <div id="church-detail-content"></div>
  </div>

  <!-- ABOUT -->
  <div class="page-section" id="page-about">
    <div class="section-header"><h2 class="section-title">&#9962; About Our Church</h2></div>
    <div class="blessing-card" style="margin-bottom:1.5rem" id="about-hero"></div>
    <div class="info-grid" id="about-info"></div>
  </div>
</main>
<!-- FOOTER -->
<footer class="site-footer">
  <div class="footer-brand" id="footer-name">{{ config('app.name', 'Grace Community Church') }}</div>
  <p class="footer-text" id="footer-info"></p>
  <p class="footer-text" style="margin-top:0.4rem">"And let us consider how we may spur one another on toward love and good deeds." &mdash; Hebrews 10:24</p>
</footer>
<!-- MODALS -->
<div class="modal-overlay" id="modal-prayer">
  <div class="modal-content">
    <div class="modal-title">Submit Prayer Request <button class="modal-close" onclick="closeModal('prayer')">&#10005;</button></div>
    <div class="form-group">
      <label class="form-label">Your Name</label>
      <input class="form-input" id="prayer-name" placeholder="Enter your name">
    </div>
    <div class="form-checkbox-row">
      <input type="checkbox" class="form-checkbox" id="prayer-anon" onchange="document.getElementById('prayer-name').disabled=this.checked">
      <label style="font-size:0.88rem;color:var(--text-secondary)">Submit anonymously</label>
    </div>
    <div class="form-group">
      <label class="form-label">Subject</label>
      <input class="form-input" id="prayer-subject" placeholder="Brief subject for your prayer request">
    </div>
    <div class="form-group">
      <label class="form-label">Prayer Request</label>
      <textarea class="form-textarea" id="prayer-request" placeholder="Share your prayer request with the church family..."></textarea>
    </div>
    <div class="form-checkbox-row">
      <input type="checkbox" class="form-checkbox" id="prayer-public" checked>
      <label style="font-size:0.88rem;color:var(--text-secondary)">Make visible to the community</label>
    </div>
    <button class="btn-primary" onclick="submitPrayer()">&#128591; Submit Prayer Request</button>
  </div>
</div>
<div class="modal-overlay" id="modal-review">
  <div class="modal-content">
    <div class="modal-title">Write a Review <button class="modal-close" onclick="closeModal('review')">&#10005;</button></div>
    <div class="form-group">
      <label class="form-label">Your Name</label>
      <input class="form-input" id="review-name" placeholder="Enter your name">
    </div>
    <div class="form-group">
      <label class="form-label">Your Email</label>
      <input class="form-input" id="review-email" placeholder="Enter your email" type="email">
    </div>
    <div class="form-group">
      <label class="form-label">Rating</label>
      <div class="star-input" id="star-input"></div>
    </div>
    <div class="form-group">
      <label class="form-label">Review Title</label>
      <input class="form-input" id="review-title" placeholder="Title for your review">
    </div>
    <div class="form-group">
      <label class="form-label">Your Review</label>
      <textarea class="form-textarea" id="review-text" placeholder="Share your experience with our church..."></textarea>
    </div>
    <button class="btn-primary" onclick="submitReview()">&#11088; Submit Review</button>
  </div>
</div>
<!-- TESTIMONY MODAL -->
<div class="modal-overlay" id="modal-testimony">
  <div class="modal-content">
    <div class="modal-title">Share Your Testimony <button class="modal-close" onclick="closeModal('testimony')">&#10005;</button></div>
    <p style="color:var(--text-secondary);font-size:0.85rem;margin-bottom:1rem;font-style:italic">"Always be prepared to give an answer to everyone who asks you to give the reason for the hope that you have." &mdash; 1 Peter 3:15</p>
    <div class="form-group">
      <label class="form-label">Your Name</label>
      <input class="form-input" id="testimony-name" placeholder="Enter your full name">
    </div>
    <div class="form-group">
      <label class="form-label">Date of Born Again</label>
      <input class="form-input" id="testimony-born-again" type="date" placeholder="When did you accept Christ?">
    </div>
    <div class="form-group">
      <label class="form-label">Baptism Date</label>
      <input class="form-input" id="testimony-baptism" type="date" placeholder="When were you baptized?">
    </div>
    <div class="form-group">
      <label class="form-label">Your Testimony</label>
      <textarea class="form-textarea" id="testimony-text" rows="6" placeholder="Share your testimony... How did God work in your life? What has He done for you? (minimum 20 characters)"></textarea>
    </div>
    <button class="btn-primary" onclick="submitTestimony()">&#10013; Submit Testimony</button>
  </div>
</div>
<!-- AUTH MODAL -->
<div class="modal-overlay" id="modal-auth">
  <div class="modal-content auth-modal">
    <div class="modal-title">
      <span id="auth-modal-title">Sign In</span>
      <button class="modal-close" onclick="closeModal('auth')">&#10005;</button>
    </div>
    <!-- Login Form -->
    <div id="auth-login-form">
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" id="auth-email" type="email" placeholder="Enter your email">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input class="form-input" id="auth-password" type="password" placeholder="Enter your password">
      </div>
      <div id="auth-error" class="auth-error" style="display:none"></div>
      <button class="btn-primary" id="auth-submit-btn" onclick="doLogin()">Sign In</button>
      <div class="auth-divider"><span>or continue with</span></div>
      <div class="auth-social-btns">
        <a class="auth-social-btn auth-google" href="/auth/google/redirect">
          <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
          Google
        </a>
        <a class="auth-social-btn auth-facebook" href="/auth/facebook/redirect">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          Facebook
        </a>
      </div>
      <div class="auth-switch">
        <button onclick="showForgotForm()">Forgot Password?</button> &middot;
        Don't have an account? <button onclick="showRegisterForm()">Sign Up</button>
      </div>
    </div>
    <!-- Forgot Password Form -->
    <div id="auth-forgot-form" style="display:none">
      <p style="color:var(--text-secondary);font-size:0.88rem;margin-bottom:1rem">Enter your email and we'll send you a reset link.</p>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" id="forgot-email-input" type="email" placeholder="Enter your email">
      </div>
      <div id="forgot-error" class="auth-error" style="display:none"></div>
      <div id="forgot-success" style="display:none;color:var(--accent-green);font-size:0.88rem;margin-bottom:0.8rem;padding:8px 12px;background:rgba(74,155,106,0.1);border-radius:8px"></div>
      <button class="btn-primary" id="forgot-submit-btn-fe" onclick="doForgotPassword()">Send Reset Link</button>
      <div class="auth-switch">
        Remember your password? <button onclick="showLoginForm()">Sign In</button>
      </div>
    </div>
    <!-- Reset Password Form (shown when token is in URL) -->
    <div id="auth-reset-form" style="display:none">
      <div class="form-group">
        <label class="form-label">New Password</label>
        <input class="form-input" id="reset-password-input" type="password" placeholder="New password (min 8 characters)">
      </div>
      <div class="form-group">
        <label class="form-label">Confirm New Password</label>
        <input class="form-input" id="reset-password-confirm" type="password" placeholder="Confirm new password">
      </div>
      <div id="reset-error" class="auth-error" style="display:none"></div>
      <div id="reset-success" style="display:none;color:var(--accent-green);font-size:0.88rem;margin-bottom:0.8rem;padding:8px 12px;background:rgba(74,155,106,0.1);border-radius:8px"></div>
      <button class="btn-primary" id="reset-submit-btn" onclick="doResetPassword()">Reset Password</button>
      <div class="auth-switch">
        <button onclick="showLoginForm()">Back to Sign In</button>
      </div>
    </div>
    <!-- Register Form -->
    <div id="auth-register-form" style="display:none">
      <div class="form-group">
        <label class="form-label">Name</label>
        <input class="form-input" id="reg-name" type="text" placeholder="Enter your full name">
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" id="reg-email" type="email" placeholder="Enter your email">
      </div>
      <!-- Dynamic custom profile fields (populated by JS) -->
      <div id="reg-custom-fields"></div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input class="form-input" id="reg-password" type="password" placeholder="Create a password (min 8 characters)">
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <input class="form-input" id="reg-password-confirm" type="password" placeholder="Confirm your password">
      </div>
      <div id="reg-error" class="auth-error" style="display:none"></div>
      <button class="btn-primary" id="reg-submit-btn" onclick="doRegister()">Create Account</button>
      <div class="auth-divider"><span>or continue with</span></div>
      <div class="auth-social-btns">
        <a class="auth-social-btn auth-google" href="/auth/google/redirect">
          <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
          Google
        </a>
        <a class="auth-social-btn auth-facebook" href="/auth/facebook/redirect">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          Facebook
        </a>
      </div>
      <div class="auth-switch">
        Already have an account? <button onclick="showLoginForm()">Sign In</button>
      </div>
    </div>
  </div>
</div>
<!-- PDF VIEWER -->
<div class="pdf-viewer-overlay" id="pdf-viewer">
  <div class="pdf-viewer-header">
    <div class="pdf-viewer-title" id="pdf-viewer-title">Book Title</div>
    <div class="pdf-viewer-controls">
      <button class="pdf-viewer-btn" onclick="pdfZoom(-0.2)" title="Zoom Out">&#8722;</button>
      <button class="pdf-viewer-btn" onclick="pdfZoom(0.2)" title="Zoom In">&#43;</button>
      <button class="pdf-viewer-btn" onclick="pdfZoom(0, true)" title="Fit to Page">Fit</button>
      <span class="pdf-viewer-page-info" id="pdf-page-info">0 / 0</span>
      <button class="pdf-viewer-btn close-btn" onclick="closePdfViewer()">&#10005; Close</button>
    </div>
  </div>
  <div class="pdf-viewer-body">
    <button class="pdf-viewer-nav-arrow prev" id="pdf-prev" onclick="pdfPrev()" disabled>&#9664;</button>
    <div class="pdf-viewer-page-wrap">
      <div class="pdf-viewer-canvas-container" id="pdf-canvas-container">
        <canvas id="pdf-canvas"></canvas>
      </div>
      <div class="pdf-viewer-loading" id="pdf-loading" style="display:none">Loading PDF...</div>
    </div>
    <button class="pdf-viewer-nav-arrow next" id="pdf-next" onclick="pdfNext()" disabled>&#9654;</button>
  </div>
</div>
<!-- NEWSLETTER POPUP -->
<div class="newsletter-popup" id="newsletter-popup">
  <div class="newsletter-popup-content">
    <button class="newsletter-popup-close" onclick="closeNewsletterPopup()">&#10005;</button>
    <div class="newsletter-popup-icon">&#128140;</div>
    <h3 style="font-family:var(--font-display);font-size:1.3rem;color:var(--cream);margin-bottom:0.4rem">Stay Connected</h3>
    <p style="font-size:0.88rem;color:var(--text-secondary);margin-bottom:1rem">Subscribe to our newsletter for weekly updates, devotionals, and church announcements.</p>
    <div class="form-group" style="margin-bottom:0.6rem">
      <input class="form-input" id="newsletter-name" placeholder="Your name (optional)" style="text-align:center">
    </div>
    <div class="form-group" style="margin-bottom:0.8rem">
      <input class="form-input" id="newsletter-email" type="email" placeholder="Enter your email address" style="text-align:center">
    </div>
    <button class="btn-primary" onclick="submitNewsletter()">&#128140; Subscribe Now</button>
    <p style="font-size:0.72rem;color:var(--text-muted);margin-top:0.6rem">We respect your privacy. Unsubscribe anytime.</p>
  </div>
</div>
<!-- NEWSLETTER INLINE (footer) -->
<div class="newsletter-footer" id="newsletter-footer">
  <div class="newsletter-footer-inner">
    <div class="newsletter-footer-text">
      <h3 style="font-family:var(--font-display);font-size:1.1rem;color:var(--cream);margin-bottom:0.3rem">&#128140; Join Our Newsletter</h3>
      <p style="font-size:0.85rem;color:var(--text-secondary)">Get weekly updates delivered to your inbox.</p>
    </div>
    <div class="newsletter-footer-form">
      <input class="form-input" id="newsletter-footer-email" type="email" placeholder="Your email address" style="flex:1;min-width:200px">
      <button class="btn-primary" style="width:auto;padding:10px 24px;white-space:nowrap" onclick="submitFooterNewsletter()">Subscribe</button>
    </div>
  </div>
</div>
<!-- PROFILE EDIT MODAL -->
<div class="modal-overlay" id="modal-profile">
  <div class="modal-content auth-modal">
    <div class="modal-title">
      <span>Edit Profile</span>
      <button class="modal-close" onclick="closeModal('profile')">&#10005;</button>
    </div>
    <div class="form-group">
      <label class="form-label">Name</label>
      <input class="form-input" id="profile-name" type="text" placeholder="Your full name">
    </div>
    <div class="form-group">
      <label class="form-label">Email</label>
      <input class="form-input" id="profile-email" type="email" placeholder="Your email">
    </div>
    <!-- Dynamic custom profile fields (populated by JS) -->
    <div id="profile-custom-fields"></div>
    <div style="border-top:1px solid var(--border);margin:1rem 0;padding-top:1rem">
      <p style="color:var(--text-muted);font-size:0.82rem;margin-bottom:0.8rem">Leave password fields empty to keep current password.</p>
      <div class="form-group">
        <label class="form-label">New Password</label>
        <input class="form-input" id="profile-password" type="password" placeholder="New password (optional)">
      </div>
      <div class="form-group">
        <label class="form-label">Confirm New Password</label>
        <input class="form-input" id="profile-password-confirm" type="password" placeholder="Confirm new password">
      </div>
    </div>
    <div id="profile-error" class="auth-error" style="display:none"></div>
    <div id="profile-success" style="display:none;color:var(--accent-green);font-size:0.88rem;margin-bottom:0.8rem;padding:8px 12px;background:rgba(74,155,106,0.1);border-radius:8px"></div>
    <button class="btn-primary" id="profile-submit-btn" onclick="doUpdateProfile()">Save Changes</button>
  </div>
</div>
<!-- TOAST -->
<div class="toast" id="toast"></div>
<!-- PWA INSTALL -->
<div class="pwa-install" id="pwa-install">
  <div class="pwa-install-text">
    <div class="pwa-install-title">Install App</div>
    <div class="pwa-install-desc">Add to home screen for quick access</div>
  </div>
  <button class="pwa-install-btn" id="pwa-install-btn">Install</button>
  <button class="pwa-install-close" onclick="dismissPwaInstall()">&#10005;</button>
</div>
