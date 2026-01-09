const disableElement = (element) => {
    element.style.pointerEvents = 'none'
    element.style.opacity = '0.5'
}

const enableElement = (element) => {
    element.style.pointerEvents = 'auto'
    element.style.opacity = '1'
}

function game_fav_button_handler(element, id) {
    const buttonAction = element.getAttribute('data-action')
    const nonce = 'game_fav_button_nonce'

    disableElement(element)
    fetch(ajax_object.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: new URLSearchParams({
            action: 'game_fav_button',
            button_action: buttonAction,
            game_id: id,
            nonce: ajax_object[nonce]
        })
    })
        .then(response => response.json())
        .then(data => {
            data = data.data
            element.childNodes.forEach(node => {
                if (node.nodeType === Node.TEXT_NODE) {
                    node.textContent = data.text
                }
            })
            element.setAttribute('data-action', data.action)
            enableElement(element)
        })
        .catch(error => {
            alert('Failed to complete your request!')
            enableElement(element)
        })
}

const bricksSyncSliders = (main_id, thumb_id) => {
    let initTimeout, resizeTimeout

    const syncSliders = () => {
        // Check if bricksData exists and has splideInstances
        if (!window.bricksData || !window.bricksData.splideInstances) {
            return false
        }

        const main = window.bricksData.splideInstances[main_id],
            thumbnail = window.bricksData.splideInstances[thumb_id]

        // Only sync if both sliders exist and are valid
        if (main && thumbnail) {
            try {
                // Sync main slider with thumbnail (when thumbnail moves, main moves)
                // main.sync(thumbnail) means: when thumbnail moves, main will move
                if (typeof main.sync === 'function') {
            main.sync(thumbnail)
                } else if (typeof thumbnail.sync === 'function') {
                    thumbnail.sync(main)
                }
                
                // Add event listeners as backup for all synced sliders
                // This ensures autoplay and other movement types work correctly
                thumbnail.on('moved', (newIndex) => {
                    if (main && typeof main.go === 'function') {
                        const currentMainIndex = main.index || 0
                        if (currentMainIndex !== newIndex) {
                            main.go(newIndex)
                        }
                    }
                })
                
                // Also listen for active event
                thumbnail.on('active', (newIndex) => {
                    if (main && typeof main.go === 'function') {
                        const currentMainIndex = main.index || 0
                        if (currentMainIndex !== newIndex) {
                            main.go(newIndex)
                        }
                    }
                })
                
                return true
            } catch (e) {
                console.warn('Slider sync failed:', e)
                return false
            }
        }

        return false
    }

    const initSync = () => {
        // Try a few times to wait for Bricks to initialize
        let attempts = 0
        const maxAttempts = 20
        
        const trySync = () => {
            attempts++
            if (syncSliders() || attempts >= maxAttempts) {
                clearTimeout(initTimeout)
            } else {
                initTimeout = setTimeout(trySync, 300)
            }
        }
        
        initTimeout = setTimeout(trySync, 200)
    }

    const syncOnResize = () => {
        resizeTimeout = setTimeout(syncSliders, 260)
    }

    document.addEventListener('DOMContentLoaded', initSync)
    window.addEventListener("resize", syncOnResize)

    return {
        sync: syncSliders
    }
}


// Suppress Bricks slider errors for query loop sliders that haven't loaded yet
const originalError = console.error;
console.error = function(...args) {
    // Suppress "Slide is undefined" errors for query loop sliders
    const errorMessage = args[0]?.toString() || '';
    if (errorMessage.includes('Slide is undefined')) {
        const sliderId = errorMessage.match(/Slide is undefined:\s*(\w+)/)?.[1];
        // These are query loop sliders that load dynamically or have no content - errors are expected
        if (sliderId && ['lrnfev', 'bzbfwz', 'klnciu', 'posusd'].includes(sliderId)) {
            return; // Suppress this error
        }
    }
    originalError.apply(console, args);
};

// Force query loops to load if they haven't loaded automatically
const triggerQueryLoops = () => {
    const queryTrails = document.querySelectorAll('.brx-query-trail[data-query-element-id]');
    queryTrails.forEach(trail => {
        const elementId = trail.getAttribute('data-query-element-id');
        const queryVars = trail.getAttribute('data-query-vars');
        const page = trail.getAttribute('data-page') || '1';
        
        if (elementId && queryVars && !trail.querySelector('.splide__slide:not(.brx-query-trail)')) {
            try {
                const vars = JSON.parse(queryVars);
                const postId = window.bricksData?.postId || 81;
                const nonce = window.bricksData?.nonce || '';
                
                // Trigger Bricks query loop load
                if (window.bricksData && typeof window.bricksQueryLoop === 'function') {
                    window.bricksQueryLoop.loadQueryPage(elementId, vars, page, postId);
                } else if (window.bricksData && window.bricksData.queryLoopInstances) {
                    // Try alternative method
                    const instance = window.bricksData.queryLoopInstances[elementId];
                    if (instance && typeof instance.loadPage === 'function') {
                        instance.loadPage(page);
                    }
                }
            } catch (e) {
                console.warn('Failed to trigger query loop for', elementId, e);
            }
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    // Trigger query loops after a short delay to ensure Bricks is ready
    setTimeout(triggerQueryLoops, 500);
    // Also trigger on window load as fallback
    window.addEventListener('load', () => {
        setTimeout(triggerQueryLoops, 1000);
    });
    // Initialize slider syncs - they will retry until Bricks is ready
    // Note: lrnfev is in a query loop, so it may not be available immediately
    const playerPassSlider = bricksSyncSliders('zhiebz', 'lrnfev')      // Play Pass: Main hero + Featured Titles
    const playerRewardsSlider = bricksSyncSliders('znfbrp', 'qgzcxq')  // Player Rewards: Main hero + Featured Early Access Titles
    const multipassSlider = bricksSyncSliders('uhsjsd', 'wxyovo')      // Multipass: Main hero + Featured Early Access Titles
    const archiveHeroSlider = bricksSyncSliders('wfoabz', 'oeieah')    // Archive Hero (has custom mouseover handler)
    const subscriptionPlansSlider = bricksSyncSliders('hvncdm', 'wcrxzo') // Subscription Plans
    const myGamesSlider = bricksSyncSliders('qrvcyr', 'ucsibe')        // My Games
    
    // Generic function to set up active thumbnail click-to-navigate for any slider pair
    const setupThumbnailClickToNavigate = (thumbId, thumbElementId, mainId, sliderName) => {
        const setupHandler = () => {
            if (!window.bricksData?.splideInstances) {
                setTimeout(setupHandler, 200)
                return
            }
            
            const thumbElement = document.getElementById(thumbElementId)
            const mainElement = document.getElementById(`brxe-${mainId}`)
            
            if (!thumbElement || !mainElement || thumbElement.dataset.clickToNavigateSetup === 'true') {
                return
            }
            
            thumbElement.dataset.clickToNavigateSetup = 'true'
            
            // Use event delegation on the thumbnail slider container
            thumbElement.addEventListener('click', function(e) {
                // Find the clicked slide
                let clickedSlide = e.target.closest('.splide__slide:not(.brx-query-trail)')
                if (!clickedSlide) {
                    return
                }
                
                // Find the link (could be the slide itself or inside it)
                const link = clickedSlide.tagName === 'A' ? clickedSlide : clickedSlide.querySelector('a')
                
                if (!link) {
                    return
                }
                
                // Check if this slide is already active - if so, navigate to the link
                const isActive = clickedSlide.classList.contains('is-active')
                
                if (isActive) {
                    // Try to find the URL from various possible sources
                    let targetUrl = link.href || 
                                   link.dataset.href || 
                                   link.dataset.link || 
                                   link.dataset.url || 
                                   link.dataset.permalink ||
                                   (link.getAttribute('href')) ||
                                   null
                    
                    // If still no URL, try to find a nested link
                    if (!targetUrl || targetUrl === '#' || targetUrl === window.location.href) {
                        const nestedLink = link.querySelector('a[href]')
                        if (nestedLink && nestedLink.href) {
                            targetUrl = nestedLink.href
                        }
                    }
                    
                    // If still no URL, try to get it from the main slider slide
                    if (!targetUrl || targetUrl === '#' || targetUrl === window.location.href) {
                        // First try aria-controls (for Featured Titles style)
                        const ariaControls = link.getAttribute('aria-controls') || clickedSlide.getAttribute('aria-controls')
                        if (ariaControls) {
                            const mainSlide = document.getElementById(ariaControls)
                            if (mainSlide) {
                                // Try to find a link in the main slide (check for any link with href)
                                const mainSlideLinks = mainSlide.querySelectorAll('a[href]')
                                for (let mainLink of mainSlideLinks) {
                                    if (mainLink.href && mainLink.href !== '#' && mainLink.href !== window.location.href && !mainLink.href.includes('javascript:')) {
                                        targetUrl = mainLink.href
                                        break
                                    }
                                }
                                // Or check data attributes on main slide
                                if ((!targetUrl || targetUrl === '#') && mainSlide.dataset.permalink) {
                                    targetUrl = mainSlide.dataset.permalink
                                }
                            }
                        }
                        
                        // If still no URL and no aria-controls, find main slide by index
                        if ((!targetUrl || targetUrl === '#') && !ariaControls) {
                            // Get the index of the clicked thumbnail slide
                            const allThumbSlides = Array.from(thumbElement.querySelectorAll('.splide__slide:not(.brx-query-trail)'))
                            const thumbIndex = allThumbSlides.indexOf(clickedSlide)
                            
                            if (thumbIndex >= 0) {
                                // Find the corresponding main slide by index
                                const allMainSlides = Array.from(mainElement.querySelectorAll('.splide__slide:not(.brx-query-trail)'))
                                if (thumbIndex < allMainSlides.length) {
                                    const mainSlide = allMainSlides[thumbIndex]
                                    // Try to find a link in the main slide
                                    const mainSlideLinks = mainSlide.querySelectorAll('a[href]')
                                    for (let mainLink of mainSlideLinks) {
                                        if (mainLink.href && mainLink.href !== '#' && mainLink.href !== window.location.href && !mainLink.href.includes('javascript:')) {
                                            targetUrl = mainLink.href
                                            break
                                        }
                                    }
                                    // Or check data attributes on main slide
                                    if ((!targetUrl || targetUrl === '#') && mainSlide.dataset.permalink) {
                                        targetUrl = mainSlide.dataset.permalink
                                    }
                                }
                            }
                        }
                    }
                    
                    // If we found a valid URL, navigate to it
                    if (targetUrl && targetUrl !== '#' && targetUrl !== window.location.href && !targetUrl.includes('javascript:')) {
                        // Prevent other handlers from interfering
                        e.preventDefault()
                        e.stopPropagation()
                        // Navigate directly to the link's href
                        window.location.href = targetUrl
                        return
                    }
                }
            }, { capture: true, passive: false })
        }
        
        setTimeout(setupHandler, 1000)
    }
    
    // Set up click-to-navigate for all thumbnail sliders
    setupThumbnailClickToNavigate('lrnfev', 'brxe-lrnfev', 'zhiebz', 'Play Pass Featured Titles')
    setupThumbnailClickToNavigate('qgzcxq', 'brxe-qgzcxq', 'znfbrp', 'Player Rewards Featured Early Access')
    setupThumbnailClickToNavigate('wxyovo', 'brxe-wxyovo', 'uhsjsd', 'Multipass Featured Early Access')
    
    // Unified polling function - consolidates setupSliderPolling and setupSliderPollingFrequent
    // This ensures autoplay works even if the basic sync() method doesn't catch it
    const setupSliderPolling = (mainId, thumbId, sliderName, interval = 100) => {
        const setupPolling = () => {
            if (!window.bricksData?.splideInstances) {
                setTimeout(setupPolling, 200)
                return
            }
            
            const mainSlider = window.bricksData.splideInstances[mainId]
            const thumbSlider = window.bricksData.splideInstances[thumbId]
            
            if (!mainSlider || !thumbSlider || thumbSlider._jampackPollingSetup === true) {
                return
            }
            
            thumbSlider._jampackPollingSetup = true
            
            let lastThumbIndex = thumbSlider.index || 0
            
            // Get slide counts helper
            const getSlideCount = (slider) => {
                return slider?.Components?.Slides?.slides?.length || 0
            }
            
            // Validate and clamp index helper
            const validateIndex = (index, mainCount, thumbCount) => {
                let validIndex = index
                if (mainCount > 0 && validIndex >= mainCount) {
                    validIndex = mainCount - 1
                }
                if (thumbCount > 0 && validIndex >= thumbCount) {
                    validIndex = thumbCount - 1
                }
                return Math.max(0, validIndex)
            }
            
            // Poll to check for changes
            const pollInterval = setInterval(() => {
                const currentThumbIndex = thumbSlider.index ?? -1
                const currentMainIndex = mainSlider.index ?? -1
                
                const thumbSlideCount = getSlideCount(thumbSlider)
                const mainSlideCount = getSlideCount(mainSlider)
                
                // If thumbnail slider index changed, update main slider
                if (currentThumbIndex >= 0 && currentThumbIndex !== lastThumbIndex) {
                    // Detect wrap-around
                    const isWrapAround = thumbSlideCount > 0 && 
                        ((lastThumbIndex === thumbSlideCount - 1 && currentThumbIndex === 0) ||
                         (lastThumbIndex === 0 && currentThumbIndex === thumbSlideCount - 1))
                    
                    let targetIndex = currentThumbIndex
                    
                    // If wrapping from last to first, explicitly ensure we go to 0
                    if (isWrapAround && lastThumbIndex === thumbSlideCount - 1 && currentThumbIndex === 0) {
                        targetIndex = 0
                    }
                    
                    targetIndex = validateIndex(targetIndex, mainSlideCount, thumbSlideCount)
                    lastThumbIndex = currentThumbIndex
                    
                    // Update main slider if it's different
                    if (currentMainIndex !== targetIndex && typeof mainSlider.go === 'function') {
                        const delay = isWrapAround ? 50 : 10
                        setTimeout(() => {
                            const finalIndex = thumbSlider.index ?? targetIndex
                            const finalTargetIndex = validateIndex(finalIndex, mainSlideCount, thumbSlideCount)
                            mainSlider.go(finalTargetIndex)
                        }, delay)
                    }
                }
            }, interval)
            
            // Clean up on slider destroy
            thumbSlider.on('destroy', () => {
                clearInterval(pollInterval)
            })
        }
        
        setTimeout(setupPolling, 1000)
    }
    
    // Set up frequent polling (50ms) for sliders with autoplay
    setupSliderPolling('zhiebz', 'lrnfev', 'Play Pass (Featured Titles)', 50)
    setupSliderPolling('znfbrp', 'qgzcxq', 'Player Rewards (Featured Early Access Titles)', 50)
    setupSliderPolling('uhsjsd', 'wxyovo', 'Multipass (Featured Early Access Titles)', 50)
    
    // Set up standard polling (100ms) for other sliders
    setupSliderPolling('wfoabz', 'oeieah', 'Archive Hero', 100)
    setupSliderPolling('hvncdm', 'wcrxzo', 'Subscription Plans', 100)
    setupSliderPolling('qrvcyr', 'ucsibe', 'My Games', 100)

    // Featured Titles slider click handler - sync with main hero slider (same as keyboard)
    // Use event delegation on the slider container for reliability
    const setupFeaturedTitlesDelegation = () => {
        const featuredSliderElement = document.getElementById('brxe-lrnfev')
        
        if (!featuredSliderElement || featuredSliderElement.dataset.delegationSetup === 'true') {
            return
        }
        
        featuredSliderElement.dataset.delegationSetup = 'true'
        
        // Use event delegation - catch clicks on any slide or link inside
        featuredSliderElement.addEventListener('click', function(e) {
            // Find the clicked slide (could be the link itself or inside a slide)
            let clickedSlide = e.target.closest('.splide__slide:not(.brx-query-trail)')
            if (!clickedSlide) {
                return
            }
            
            // Find the link
            const link = clickedSlide.classList.contains('brxe-xffsfw') 
                ? clickedSlide 
                : clickedSlide.querySelector('a.brxe-xffsfw') || clickedSlide.querySelector('a')
            
            if (!link) {
                return
            }
            
            // Try to find the URL from various possible sources
            let targetUrl = link.href || 
                           link.dataset.href || 
                           link.dataset.link || 
                           link.dataset.url || 
                           link.dataset.permalink ||
                           (link.getAttribute('href')) ||
                           null
            
            // If still no URL, try to find a nested link
            if (!targetUrl || targetUrl === '#' || targetUrl === window.location.href) {
                const nestedLink = link.querySelector('a[href]')
                if (nestedLink && nestedLink.href) {
                    targetUrl = nestedLink.href
                }
            }
            
            // If still no URL, try to get it from the main slider slide that this controls
            if (!targetUrl || targetUrl === '#' || targetUrl === window.location.href) {
                const ariaControls = link.getAttribute('aria-controls')
                if (ariaControls) {
                    const mainSlide = document.getElementById(ariaControls)
                    if (mainSlide) {
                        // Try to find a link in the main slide
                        const mainSlideLink = mainSlide.querySelector('a[href]')
                        if (mainSlideLink && mainSlideLink.href) {
                            targetUrl = mainSlideLink.href
                        }
                        // Or check data attributes on main slide
                        if ((!targetUrl || targetUrl === '#') && mainSlide.dataset.permalink) {
                            targetUrl = mainSlide.dataset.permalink
                        }
                    }
                }
            }
            
            // Check if this slide is already active - if so, navigate to the link
            const isActive = clickedSlide.classList.contains('is-active')
            
            if (isActive && targetUrl && targetUrl !== '#' && targetUrl !== window.location.href && !targetUrl.includes('javascript:')) {
                // Prevent other handlers from interfering
                e.preventDefault()
                e.stopPropagation()
                e.stopImmediatePropagation()
                // Navigate directly to the link's href
                window.location.href = targetUrl
                return
            }
            
            // If not active, prevent default and move sliders
            e.preventDefault()
            e.stopPropagation()
            e.stopImmediatePropagation()
            
            // Get sliders
            if (!window.bricksData?.splideInstances) {
                console.warn('[Featured Titles] Sliders not available')
                return
            }
            
            const mainSlider = window.bricksData.splideInstances['zhiebz']
            const featuredSlider = window.bricksData.splideInstances['lrnfev']
            
            if (!mainSlider || !featuredSlider) {
                console.warn('[Featured Titles] Sliders not initialized')
                return
            }
            
            // Find slide index
            const allSlides = Array.from(featuredSliderElement.querySelectorAll('.splide__slide:not(.brx-query-trail)'))
            const slideIndex = allSlides.indexOf(clickedSlide)
            
            if (slideIndex >= 0) {
                // Move featured slider first
                if (featuredSlider && typeof featuredSlider.go === 'function') {
                    featuredSlider.go(slideIndex)
                }
                // Move main slider to update content
                if (mainSlider && typeof mainSlider.go === 'function') {
                    setTimeout(() => {
                        mainSlider.go(slideIndex)
                    }, 10)
                }
            }
        }, { capture: true, passive: false })
    }
    
    // Set up event delegation immediately (works even before slides load)
    setupFeaturedTitlesDelegation()
    

    // Archive hero slider thumbnails interaction
    const initArchiveHeroThumbs = () => {
        if (!window.bricksData?.splideInstances) {
            setTimeout(initArchiveHeroThumbs, 100)
            return
        }

    const archiveHeroSliderThumbs = document.getElementById('brxe-oeieah-track')?.getElementsByTagName('a')
        const archiveHeroMainSlider = window.bricksData?.splideInstances['wfoabz']
        
        if (archiveHeroSliderThumbs && archiveHeroSliderThumbs.length > 0 && archiveHeroMainSlider) {
        for (let i = 0; i < archiveHeroSliderThumbs.length; i++) {
            archiveHeroSliderThumbs[i].addEventListener('mouseover', function(e) {
                if (e.target.closest('.splide__slide') && e.target.closest('.splide__slide').classList.contains('is-active')) {
                    return;
                }
                let actives = document.querySelectorAll('.splide__slide.is-active')
                if (actives.length > 0) {
                    for (let active of actives) {
                        active.classList.remove('is-active')
                    }
                }
                e.target.closest('.splide__slide').classList.add('is-active')
                    archiveHeroMainSlider.go(i)
                })
            }
        }
    }
    initArchiveHeroThumbs()

    // Auto-scroll sliders initialization
    const initAutoScrollSliders = () => {
        if (!window.bricksData?.splideInstances || !window.splide?.Extensions?.AutoScroll) {
            setTimeout(initAutoScrollSliders, 100)
            return
        }

        const instances = []
        instances.push(window.bricksData.splideInstances['oqcvnm'])
        instances.push(window.bricksData.splideInstances['nxdmvw'])
        instances.push(window.bricksData.splideInstances['kzjcyl'])
        
        if (instances.length > 0) {
            for (let instance of instances) {
                if (instance) {
                    instance.destroy(true);
                    instance.mount({AutoScroll: window.splide.Extensions.AutoScroll})
                }
            }
        }
    }
    setTimeout(initAutoScrollSliders, 100)
})

let deferredPrompt;
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault()
    deferredPrompt = event
    const addToHomeBtn = document.querySelector('.add-to-home-button')
    if (addToHomeBtn) {
        addToHomeBtn.addEventListener('click', () => {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                } else {
                }
                deferredPrompt = null;
            })
        })
    }
})

document.addEventListener('DOMContentLoaded', function() {
    if (document.body.classList.contains('mepr-pro-template') && !document.getElementById('back-button')) {
        const backElm = document.createElement('a');
        backElm.id = 'back-button';
        const iconSvg = `
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-left" viewBox="0 0 16 16" style="width: 30px; height: 30px">
              <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
            </svg>
        `;
        backElm.innerHTML = iconSvg;
        // Check if user has active subscription via PHP-generated data
        const hasActiveSubscription = document.body.dataset.hasActiveSubscription === 'true';
        backElm.href = hasActiveSubscription ? window.location.origin + '/play-pass/' : window.location.origin;
        backElm.style.marginRight = '15px';
        backElm.style.color = '#fff';
        const siteBranding = document.querySelector('.site-branding');
        siteBranding.style.display = 'flex';
        siteBranding.style.alignItems = 'center';
        if (siteBranding) {
            siteBranding.prepend(backElm);
            siteBranding.querySelector('img').style.height = 'auto';
        }
    }
});

window.jampackFullscreen = {

    /**
     * Launches fullscreen mode.
     * @param {string} selector - Optional CSS selector. If empty, auto-detects game elements
     * @returns {boolean} True if fullscreen was successfully launched, false otherwise
     * @example
     * // Auto-detect game element
     * jampackFullscreen.launch('');
     * 
     * // Target specific element
     * jampackFullscreen.launch('#my-game-iframe');
     */
    launch: function(selector) {
        const element = this.findGameElement(selector);
        if (!element) {
            console.warn('Jampack Fullscreen: No element found for selector:', selector);
            return false;
        }
        
        return this.enterFullscreen(element);
    },
    
    /**
     * Finds a suitable game element to make fullscreen
     * @param {string} selector - CSS selector for target element
     * @returns {Element|null} The DOM element to make fullscreen, or null if none found
     * @private
     */
    findGameElement: function(selector) {
        if (selector && selector.trim()) {
            const element = document.querySelector(selector);
            if (element) return element;
        }
        
        const gameSelectors = [
            '.game-container',
            '.game-iframe', 
            '.game-content',
            '#game-container',
            'iframe',
            'canvas'
        ];
        
        for (let sel of gameSelectors) {
            const elements = document.querySelectorAll(sel);
            if (elements.length > 0) {
                // Return the container or the element itself
                return elements[0].closest('.brxe-div, .bricks-element') || elements[0];
            }
        }
        
        return null;
    },
    
    /**
     * Enters fullscreen mode for the specified element using cross-browser API
     * @param {Element} element - DOM element to make fullscreen
     * @returns {boolean} True if fullscreen request was successful, false if not supported
     * @private
     */
    enterFullscreen: function(element) {
        if (element.requestFullscreen) {
            element.requestFullscreen();
        } else if (element.webkitRequestFullscreen) {
            element.webkitRequestFullscreen();
        } else if (element.mozRequestFullScreen) {
            element.mozRequestFullScreen();
        } else if (element.msRequestFullscreen) {
            element.msRequestFullscreen();
        } else {
            return false;
        }
        return true;
    },
    
    /**
     * Exits fullscreen mode using cross-browser API
     * @returns {void}
     * @example
     * jampackFullscreen.exit();
     */
    exit: function() {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    },
    
    /**
     * Checks if currently in fullscreen mode
     * @returns {boolean} True if any element is currently in fullscreen, false otherwise
     * @example
     * if (jampackFullscreen.isActive()) {
     *     console.log('Currently in fullscreen');
     * }
     */
    isActive: function() {
        return !!(document.fullscreenElement || 
                 document.webkitFullscreenElement || 
                 document.mozFullScreenElement || 
                 document.msFullscreenElement);
    }
};