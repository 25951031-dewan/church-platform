<div class="share-buttons">
    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" class="share-btn fb" title="Share on Facebook"><i class="fab fa-facebook-f"></i></a>
    <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($title ?? '') }}" target="_blank" rel="noopener" class="share-btn tw" title="Share on Twitter"><i class="fab fa-twitter"></i></a>
    <a href="https://wa.me/?text={{ urlencode(($title ?? '') . ' ' . url()->current()) }}" target="_blank" rel="noopener" class="share-btn wa" title="Share on WhatsApp"><i class="fab fa-whatsapp"></i></a>
    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" class="share-btn li" title="Share on LinkedIn"><i class="fab fa-linkedin-in"></i></a>
    <button onclick="navigator.clipboard.writeText(window.location.href).then(()=>alert('Link copied!'))" class="share-btn" title="Copy link"><i class="fas fa-link"></i></button>
</div>
