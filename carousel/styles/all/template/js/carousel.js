/**
 * Forum Image Carousel Extension for phpBB.
 * @package salvocortesiano/carousel
 */

(function() {
    'use strict';

    // Variabili globali
    let autoScroll = true;
    let autoScrollInterval;
    let scrollPosition = 0;
    let scrollDirection = 'rtl'; // Direzione predefinita (da destra a sinistra)
    let scrollAmount = 0.5; // Più fluido per immagini piccole
    let animationFrameId = null;
    let lastMouseX = null;
    let lastMouseMoveTime = null;
    let mouseScrollActive = false;
    let mouseScrollVelocity = 0;
    let mouseOverCarousel = false;
    let mouseScrollInterval = null;

    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM completamente caricato - inizializzazione caroselli');
        
        // Inizializza tutti i caroselli nella pagina
        const carousels = document.querySelectorAll('.forum-image-carousel');
        carousels.forEach(function(carousel) {
            // Ottieni la configurazione dagli attributi data
            if (carousel) {
                // Ottieni la direzione di scorrimento dall'attributo data
                const configDirection = carousel.dataset.scrollDirection;
                if (configDirection) {
                    scrollDirection = configDirection;
                    console.log('Direzione di scorrimento impostata a: ' + scrollDirection);
                } else {
                    console.warn('Direzione di scorrimento non trovata negli attributi data, uso predefinito: ' + scrollDirection);
                }
                
                // Ottieni la velocità di scorrimento dall'attributo data
                const configSpeed = parseInt(carousel.dataset.scrollSpeed, 10);
                if (!isNaN(configSpeed)) {
                    // Regola l'importo effettivo dello scorrimento in base all'impostazione della velocità (più basso = più veloce)
                    // 1000 (più veloce) -> importo scorrimento = 3
                    // 10000 (più lento) -> importo scorrimento = 0.3
                    scrollAmount = 3 - ((configSpeed - 1000) / 3000);
                    scrollAmount = Math.max(0.3, Math.min(3, scrollAmount));
                    console.log('Importo scorrimento impostato a: ' + scrollAmount + ' (dalla velocità: ' + configSpeed + ')');
                } else {
                    console.warn('Velocità di scorrimento non trovata negli attributi data, uso predefinito: ' + scrollAmount);
                }

                // Configurazione dinamica per velocità e direzione
                const items = carousel.querySelector('.carousel-items');
                if (items) {
                    // Duplica gli elementi per creare un loop infinito *PRIMA* di inizializzare gli script JS
                    items.innerHTML += items.innerHTML;
                }

                // Inizializza questo specifico carosello *DOPO* la duplicazione degli elementi
                initCarousel(carousel);
                initLazyLoading(carousel);
                initAutoScroll(carousel);
                initCarouselTooltip(carousel);

                // Imposta velocità e direzione dinamicamente
                if (items) {
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
            }
        });
    });

    // Gestione Intersection Observer per il caricamento lazy
    function initLazyLoading(carousel) {
        if (!carousel) return;
        
        // Verifica se IntersectionObserver è supportato
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
            
            // Osserva tutte le immagini lazy
            const items = carousel.querySelectorAll('.carousel-image.lazy');
            items.forEach(function(img) {
                imageObserver.observe(img);
            });
        } else {
            // Fallback per browser che non supportano IntersectionObserver
            loadLazyImagesFallback(carousel);
        }
    }
    
    // Funzione di fallback per browser più vecchi
    function loadLazyImagesFallback(carousel) {
        if (!carousel) return;
        
        const lazyImages = carousel.querySelectorAll('.carousel-image.lazy');
        
        // Caricamento semplice e immediato di tutte le immagini per browser senza IntersectionObserver
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

    function initCarousel(carousel) {
        if (!carousel) {
            console.error('Carosello non trovato nella pagina');
            return;
        }

        const items = carousel.querySelectorAll('.carousel-item');
        const prevBtn = carousel.querySelector('.carousel-prev');
        const nextBtn = carousel.querySelector('.carousel-next');
        
        if (items.length === 0) {
            console.error('Nessun elemento trovato nel carosello');
            return;
        }
        
        console.log('Carosello inizializzato con ' + items.length + ' elementi');

        let activeIndex = 0;
        let startX, startY, endX, endY;
        let isAutoSliding = true;
        let intervalId;
        
        // Ottieni la velocità di scorrimento dal template o usa il predefinito
        const speedAttribute = carousel.dataset.scrollSpeed;
        const autoSlideInterval = speedAttribute ? parseInt(speedAttribute, 10) : 5000; // Predefinito: 5 secondi tra gli scorrimenti automatici
        console.log('Intervallo di scorrimento automatico impostato a: ' + autoSlideInterval + 'ms');
        
        // Inizializza il carosello
        updateActiveSlide();
        startAutoSlide();

        // Event listeners per desktop
        if (prevBtn) {
            prevBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Pulsante precedente cliccato');
                showPreviousSlide();
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Pulsante successivo cliccato');
                showNextSlide();
            });
        }
        
        carousel.addEventListener('mouseenter', function() {
            pauseAutoSlide();
        });
        
        carousel.addEventListener('mouseleave', function() {
            startAutoSlide();
        });

        // Eventi touch per mobile
        carousel.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            pauseAutoSlide();
        }, false);
        
        carousel.addEventListener('touchmove', function(e) {
            if (!startX || !startY) return;
            
            endX = e.touches[0].clientX;
            endY = e.touches[0].clientY;
            
            // Rileva se si sta facendo scorrimento verticale
            if (Math.abs(endY - startY) > Math.abs(endX - startX)) {
                // Scorrimento verticale, consenti il comportamento predefinito
                return;
            }
            
            // Previeni lo scorrimento orizzontale della pagina
            e.preventDefault();
        }, false);
        
        carousel.addEventListener('touchend', function(e) {
            if (!startX) {
                startAutoSlide();
                return;
            }
            
            // Se endX non è stato impostato (tap senza movimento)
            if (!endX) {
                endX = startX;
                endY = startY;
            }
            
            const diffX = endX - startX;
            
            // Rileva la direzione dello swipe se il movimento è significativo
            if (Math.abs(diffX) > 50) {
                if (diffX > 0) {
                    showPreviousSlide();
                } else {
                    showNextSlide();
                }
            }
            
            // Reimposta i valori
            startX = null;
            startY = null;
            endX = null;
            endY = null;
            
            startAutoSlide();
        }, false);

        // Funzioni per controllare il carosello
        function showPreviousSlide() {
            activeIndex = (activeIndex === 0) ? items.length - 1 : activeIndex - 1;
            updateActiveSlide();
        }

        function showNextSlide() {
            activeIndex = (activeIndex === items.length - 1) ? 0 : activeIndex + 1;
            updateActiveSlide();
        }

        function updateActiveSlide() {
            // Trova il contenitore carousel-items
            const container = carousel.querySelector('.carousel-items');
            if (!container) return;
            
            // Aggiorna la posizione attiva
            items.forEach((item, index) => {
                if (index === activeIndex) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
        }

        function startAutoSlide() {
            if (intervalId) clearInterval(intervalId);
            intervalId = setInterval(showNextSlide, autoSlideInterval);
        }

        function pauseAutoSlide() {
            if (intervalId) clearInterval(intervalId);
        }
    }

    // Implementa lo scorrimento automatico continuo
    function initAutoScroll(carousel) {
        if (!carousel) return;
        
        const items = carousel.querySelector('.carousel-items');
        if (!items) return;

        function startScroll() {
            if (animationFrameId) return;
            scrollLoop();
        }

        function pauseScroll() {
            if (animationFrameId) {
                cancelAnimationFrame(animationFrameId);
                animationFrameId = null;
            }
        }

        function scrollLoop() {
            if (!autoScroll) return;
            
            if (scrollDirection === 'rtl') {
                scrollPosition -= scrollAmount;
            } else {
                scrollPosition += scrollAmount;
            }
            
            items.style.transform = `translateX(${scrollPosition}px)`;
            
            // Reset della posizione quando raggiunge la fine
            const itemWidth = items.firstElementChild?.offsetWidth || 0;
            const totalWidth = itemWidth * items.children.length;
            
            if (Math.abs(scrollPosition) >= totalWidth / 2) {
                scrollPosition = 0;
            }
            
            animationFrameId = requestAnimationFrame(scrollLoop);
        }

        carousel.addEventListener('mouseenter', pauseScroll);
        carousel.addEventListener('mouseleave', startScroll);
        
        startScroll();
    }

    // Tooltip responsive scuro sulle immagini del carosello
    function initCarouselTooltip(carousel) {
        if (!carousel) {
            return;
        }

        const tooltip = document.createElement('div');
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

        items.forEach(item => {
            const link = item.querySelector('a');
            if (!link) {
                return;
            }

            let title = link.getAttribute('title');
            const img = link.querySelector('img');

            if (!title && img) {
                title = img.getAttribute('alt');
            }

            if (!title) {
                return;
            }

            // Rimuovi title nativo per evitare doppio tooltip del browser
            if (link.hasAttribute('title')) {
                link.removeAttribute('title');
            }
            if (img && img.hasAttribute('title')) {
                img.removeAttribute('title');
            }

            const author = (link.getAttribute('data-author') || '').trim();
            const date = (link.getAttribute('data-date') || '').trim();
            const visits = (link.getAttribute('data-visits') || '').trim();
            const tooltipContent = buildTooltipText(title, author, visits, date, labels);

            function showTooltip(anchorEl) {
                tooltip.textContent = tooltipContent;
                tooltip.classList.add('active');
                positionTooltip(anchorEl, tooltip);
            }

            link.addEventListener('mouseenter', function() {
                showTooltip(img || link);
            });

            link.addEventListener('mousemove', function() {
                positionTooltip(img || link, tooltip);
            });

            link.addEventListener('mouseleave', function() {
                tooltip.classList.remove('active');
            });

            link.addEventListener('touchstart', function() {
                showTooltip(img || link);
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
        // Forza un reflow dopo aver impostato il testo multilinea
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
