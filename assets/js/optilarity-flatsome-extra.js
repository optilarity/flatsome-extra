/**
 * Akselos Customizer - Frontend Functionality (Vanilla JS)
 */
document.addEventListener('DOMContentLoaded', function () {
    initVideoPopups();
});

function initVideoPopups() {
    // Event delegation for video links
    document.addEventListener('click', function (e) {
        const link = e.target.closest('.open-video');
        if (link) {
            e.preventDefault();
            e.stopPropagation(); // Prevent other handlers (like Flatsome's) from triggering
            const videoUrl = link.getAttribute('href');
            if (videoUrl) {
                openAkselosVideoModal(videoUrl);
            }
        }
    }, true); // Use capture phase to intercept before other scripts
}

function openAkselosVideoModal(input) {
    let embedUrl = '';
    let isHtml = input.trim().startsWith('<iframe');

    if (!isHtml) {
        let url = input.trim();
        // Convert YouTube/Vimeo URLs to embed format
        if (url.includes('youtube.com/watch') || url.includes('youtube.com/embed/')) {
            try {
                const urlObj = new URL(url);
                let videoId = urlObj.searchParams.get('v');
                if (!videoId && url.includes('/embed/')) {
                    videoId = url.split('/embed/')[1].split('?')[0];
                }
                const list = urlObj.searchParams.get('list');
                embedUrl = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0&origin=${encodeURIComponent(window.location.origin)}`;
                if (list) embedUrl += `&list=${list}`;
            } catch (e) {
                embedUrl = url;
            }
        } else if (url.includes('youtu.be/')) {
            const videoId = url.split('/').pop().split('?')[0];
            embedUrl = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0&origin=${encodeURIComponent(window.location.origin)}`;
        } else if (url.includes('vimeo.com/')) {
            const videoId = url.split('/').pop().split('?')[0];
            embedUrl = `https://player.vimeo.com/video/${videoId}?autoplay=1`;
        } else {
            embedUrl = url;
        }
    }

    // Create modal elements
    const overlay = document.createElement('div');
    overlay.className = 'akselos-video-modal-overlay';

    const container = document.createElement('div');
    container.className = 'akselos-video-modal-container';

    const closeBtn = document.createElement('div');
    closeBtn.className = 'akselos-video-modal-close';
    closeBtn.innerHTML = '&times;';

    const iframeWrapper = document.createElement('div');
    iframeWrapper.className = 'akselos-video-iframe-wrapper';

    const standardAllow = "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share";

    if (isHtml) {
        iframeWrapper.innerHTML = input;
        const innerIframe = iframeWrapper.querySelector('iframe');
        if (innerIframe) {
            innerIframe.style.width = '100%';
            innerIframe.style.height = '100%';
            innerIframe.style.position = 'absolute';
            innerIframe.style.top = '0';
            innerIframe.style.left = '0';
            innerIframe.setAttribute('allow', standardAllow);
            innerIframe.setAttribute('allowfullscreen', 'true');
            innerIframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
        }
    } else {
        const iframe = document.createElement('iframe');
        iframe.src = embedUrl;
        iframe.allow = standardAllow;
        iframe.allowFullscreen = true;
        iframe.referrerPolicy = "strict-origin-when-cross-origin";
        iframeWrapper.appendChild(iframe);
    }

    // Assemble
    container.appendChild(closeBtn);
    container.appendChild(iframeWrapper);
    overlay.appendChild(container);
    document.body.appendChild(overlay);

    // Animation
    setTimeout(() => overlay.classList.add('active'), 10);
    document.body.style.overflow = 'hidden';

    // Close logic
    const closeModal = () => {
        overlay.classList.remove('active');
        // Stop video by removing iframe contents
        iframeWrapper.innerHTML = '';
        setTimeout(() => {
            if (overlay.parentNode) document.body.removeChild(overlay);
            document.body.style.overflow = '';
        }, 300);
    };

    closeBtn.onclick = closeModal;
    overlay.onclick = (e) => {
        if (e.target === overlay) closeModal();
    };

    const escHandler = (e) => {
        if (e.key === 'Escape') {
            closeModal();
            document.removeEventListener('keydown', escHandler);
        }
    };
    document.addEventListener('keydown', escHandler);
}
