/**
 * Forum Image Carousel Extension for phpBB.
 * @package salvocortesiano/carousel
 */

(function() {
    'use strict';

    // Global variables
    let autoScroll = true;
    let autoScrollInterval;
    let scrollPosition = 0;
    let scrollDirection = 'rtl'; // Default direction (right to left)
    let scrollAmount = 0.5; // Più fluido per immagini piccole
    let animationFrameId = null;
    let lastMouseX = null;
    let lastMouseMoveTime = null;
    let mouseScrollActive = false;
    let mouseScrollVelocity = 0;
    let mouseOverCarousel = false;
    let mouseScrollInterval = null;

    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM fully loaded - initializing carousel');
        
        // Get configuration from data attributes
        const carousel = document.querySelector('.forum-image-carousel');
        if (carousel) {
            // Get scroll direction from data attribute
            const configDirection = carousel.dataset.scrollDirection;
            if (configDirection) {
                scrollDirection = configDirection;
                console.log('Scroll direction set to: ' + scrollDirection);
            } else {
                console.warn('Scroll direction not found in data attributes, using default: ' + scrollDirection);
            }
            
            // Get scroll speed from data attribute
            const configSpeed = parseInt(carousel.dataset.scrollSpeed, 10);
            if (!isNaN(configSpeed)) {
                // Adjust the actual scroll amount based on the speed setting (lower = faster)
                // 1000 (fastest) -> scroll amount = 3
                // 10000 (slowest) -> scroll amount = 0.3
                scrollAmount = 3 - ((configSpeed - 1000) / 3000);
                scrollAmount = Math.max(0.3, Math.min(3, scrollAmount));
                console.log('Scroll amount set to: ' + scrollAmount + ' (from speed: ' + configSpeed + ')');
            } else {
                console.warn('Scroll speed not found in data attributes, using default: ' + scrollAmount);
            }
        }
        
        // Configurazione dinamica per velocità e direzione
        const items = document.querySelector('.carousel-items');
        if (items) {
            items.innerHTML += items.innerHTML; // Duplica il contenuto per lo scorrimento infinito
        }
        // Imposta velocità e direzione dinamicamente
        if (carousel && items) {
            let speed = parseInt(carousel.dataset.scrollSpeed, 10);
            if (isNaN(speed) || speed < 1000) speed = 10000; // fallback
            // Calcola durata animazione: più basso = più veloce
            // Esempio: 1000ms = 10s, 10000ms = 60s
            let duration = Math.round(10 + (speed - 1000) / 200); // mappatura personalizzata
            items.style.animationDuration = duration + 's';
            // Direzione
            let direction = carousel.dataset.scrollDirection === 'ltr' ? 'normal' : 'reverse';
            items.style.animationDirection = direction;
        }

        // Inizializza dopo la duplicazione così anche le copie hanno i listener
        initLazyLoading();
        initAutoScroll();
        initCarouselTooltip();
    });

    // Handle Intersection Observer for lazy loading
    function initLazyLoading() {
        // Check if IntersectionObserver is supported
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const lazyImage = entry.target;
                        const src = lazyImage.getAttribute('data-src');
                        
                        if (src) {
                            lazyImage.src = src;
                            lazyImage.classList.remove('lazy');
                            lazyImage.classList.add('loaded');
                            lazyImage.removeAttribute('data-src');
                            observer.unobserve(lazyImage);
                        }
                    }
                });
            }, {
                root: null, // viewport
                rootMargin: '0px',
                threshold: 0.1
            });
            
            // Observe all lazy images
            document.querySelectorAll('.carousel-image.lazy').forEach(function(img) {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for browsers that don't support IntersectionObserver
            loadLazyImagesFallback();
        }
    }
    
    // Fallback function for older browsers
    function loadLazyImagesFallback() {
        const lazyImages = document.querySelectorAll('.carousel-image.lazy');
        
        // Simple, immediate loading of all images for browsers without IntersectionObserver
        lazyImages.forEach(function(lazyImage) {
            const src = lazyImage.getAttribute('data-src');
            if (src) {
                lazyImage.src = src;
                lazyImage.classList.remove('lazy');
                lazyImage.classList.add('loaded');
                lazyImage.removeAttribute('data-src');
            }
        });
    }

    // Removed initCarousel() as it conflicts with the desired continuous scroll behavior
    // All relevant logic is now handled by initAutoScroll() and its helper functions.

    // Implement automatic continuous scrolling
    function initAutoScroll() {
        const carousel = document.querySelector('.forum-image-carousel');
        if (!carousel) return;
        
        const itemsContainer = carousel.querySelector('.carousel-items');
        if (!itemsContainer) return;
        
        let isMouseOver = false;
        let isTouching = false;
        
        // Set up event listeners to pause auto-scrolling during user interaction
        carousel.addEventListener('mouseenter', function() {
            isMouseOver = true;
            pauseScroll();
        });
        
        carousel.addEventListener('mouseleave', function() {
            isMouseOver = false;
            if (!isTouching) {
                startScroll();
            }
        });
        
        carousel.addEventListener('touchstart', function() {
            isTouching = true;
            pauseScroll();
        });
        
        carousel.addEventListener('touchend', function() {
            isTouching = false;
            if (!isMouseOver) {
                startScroll();
            }
        });
        
        // Initialize scroll position
        if (scrollDirection === 'rtl') {
            // Start from left if scrolling right to left
            scrollPosition = 0;
        } else {
            // Start from right if scrolling left to right
            scrollPosition = itemsContainer.scrollWidth - itemsContainer.offsetWidth;
        }
        
        // Start the automatic scrolling
        startScroll();
        
        function startScroll() {
            if (animationFrameId === null) {
                console.log('Starting auto scroll in direction: ' + scrollDirection);
                scrollLoop();
            }
        }
        
        function pauseScroll() {
            if (animationFrameId !== null) {
                console.log('Pausing auto scroll');
                clearTimeout(animationFrameId);
                animationFrameId = null;
            }
        }
        
        function scrollLoop() {
            const maxScroll = itemsContainer.scrollWidth - itemsContainer.offsetWidth;
            const minScrollAmount = 0.2;
            const effectiveScrollAmount = Math.max(scrollAmount, minScrollAmount);
            
            if (scrollDirection === 'rtl') {
                scrollPosition += effectiveScrollAmount;
                if (scrollPosition >= maxScroll) {
                    // Smooth reset to start if at the end
                    itemsContainer.style.scrollBehavior = 'auto'; // Disable smooth scroll for instant jump
                    scrollPosition = 0; // Jump to the beginning
                    itemsContainer.scrollLeft = scrollPosition;
                    // Re-enable smooth scroll after a brief moment if needed for manual interaction
                    setTimeout(() => itemsContainer.style.scrollBehavior = 'smooth', 50);
                }
            } else {
                scrollPosition -= effectiveScrollAmount;
                if (scrollPosition <= 0) {
                    // Smooth reset to end if at the beginning
                    itemsContainer.style.scrollBehavior = 'auto'; // Disable smooth scroll for instant jump
                    scrollPosition = maxScroll; // Jump to the end
                    itemsContainer.scrollLeft = scrollPosition;
                    // Re-enable smooth scroll after a brief moment if needed for manual interaction
                    setTimeout(() => itemsContainer.style.scrollBehavior = 'smooth', 50);
                }
            }
            itemsContainer.scrollLeft = scrollPosition;
            animationFrameId = requestAnimationFrame(scrollLoop); // Use requestAnimationFrame for smoother animation
        }
        
        // Update scroll calculations on window resize
        window.addEventListener('resize', function() {
            // Recalculate scrollPosition to maintain relative position
            // Ensure itemsContainer.scrollWidth - itemsContainer.offsetWidth is not zero to prevent NaN
            const currentMaxScroll = itemsContainer.scrollWidth - itemsContainer.offsetWidth;
            if (currentMaxScroll > 0) {
                const currentRatio = scrollPosition / currentMaxScroll;
                const newMaxScroll = itemsContainer.scrollWidth - itemsContainer.offsetWidth;
                scrollPosition = currentRatio * newMaxScroll;
            }
            itemsContainer.scrollLeft = scrollPosition; // Apply new position immediately
        });
        
        console.log('Auto scrolling initialized');
    }

    // Tooltip responsive scuro sulle immagini del carosello
    function initCarouselTooltip() {
        const carousel = document.querySelector('.forum-image-carousel');
        if (!carousel) return;

        let tooltip = document.createElement('div');
        tooltip.className = 'carousel-tooltip';
        document.body.appendChild(tooltip);

        const labels = {
            postedBy: carousel.getAttribute('data-label-posted-by') || 'Postato da:',
            visitedBy: carousel.getAttribute('data-label-visited-by') || 'Visitato da:',
            postedOn: carousel.getAttribute('data-label-posted-on') || 'In data:',
            users: carousel.getAttribute('data-label-users') || 'utenti',
            user: carousel.getAttribute('data-label-user') || 'utente'
        };

        const items = carousel.querySelectorAll('.carousel-item');

        items.forEach(function(item) {
            const link = item.querySelector('a');
            const img = item.querySelector('img');
            if (!img) return;

            if (img.hasAttribute('title')) {
                img.removeAttribute('title');
            }

            const title = (link && link.getAttribute('title')) || img.getAttribute('alt') || 'Immagine';
            const author = ((link && link.getAttribute('data-author')) || '').trim();
            const date = ((link && link.getAttribute('data-date')) || '').trim();
            const visits = ((link && link.getAttribute('data-visits')) || '').trim();
            const tooltipContent = buildTooltipText(title, author, visits, date, labels);

            if (link && link.hasAttribute('title')) {
                link.removeAttribute('title');
            }

            function showTooltip() {
                tooltip.textContent = tooltipContent;
                tooltip.classList.add('active');
                positionTooltip(img, tooltip);
            }

            img.addEventListener('mouseenter', showTooltip);
            img.addEventListener('mousemove', function() {
                positionTooltip(img, tooltip);
            });
            img.addEventListener('mouseleave', function() {
                tooltip.classList.remove('active');
            });
            img.addEventListener('touchstart', function() {
                showTooltip();
                setTimeout(function() {
                    tooltip.classList.remove('active');
                }, 2000);
            }, { passive: true });
        });
    }

    function buildTooltipText(title, author, visits, date, labels) {
        // Multilinea come nel riferimento:
        // Titolo
        // Postato da: ...
        // Visitato da: ...
        // In data: ...
        const lines = [title];

        if (author) {
            lines.push(labels.postedBy + ' ' + author);
        }

        if (visits !== '' && !isNaN(visits)) {
            const count = parseInt(visits, 10);
            const usersLabel = count === 1 ? labels.user : labels.users;
            lines.push(labels.visitedBy + ' ' + count + ' ' + usersLabel);
        }

        if (date) {
            lines.push(labels.postedOn + ' ' + date);
        }

        return lines.join('\n');
    }

    function positionTooltip(anchorEl, tooltip) {
        if (!anchorEl) {
            return;
        }

        const rect = anchorEl.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();

        let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
        let top = rect.top - tooltipRect.height - 10;

        if (left < 8) {
            left = 8;
        }
        if (left + tooltipRect.width > window.innerWidth - 8) {
            left = window.innerWidth - tooltipRect.width - 8;
        }
        if (top < 8) {
            top = rect.bottom + 10;
        }

        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
    }
})();
