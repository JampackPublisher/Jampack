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

/**
 * Sets up synchronization between main and thumbnail sliders with event listeners.
 * @param {string} main_id - The ID of the main slider instance in bricksData.splideInstances.
 * @param {string} thumb_id - The ID of the thumbnail slider instance in bricksData.splideInstances.
 * @returns {Object} An object containing a sync function to manually trigger synchronization.
 */

const bricksSyncSliders = (main_id, thumb_id) => {
    let initTimeout, resizeTimeout

    const syncSliders = () => {
        const main = bricksData.splideInstances[main_id],
            thumbnail = bricksData.splideInstances[thumb_id]

        if (main && thumbnail) {
            main.sync(thumbnail)
        }

        clearTimeout(initTimeout)
    }

    const initSync = () => {
        initTimeout = setTimeout(syncSliders, 50)
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


document.addEventListener('DOMContentLoaded', () => {
    const playerPassSlider = bricksSyncSliders('zhiebz', 'lrnfev')
    playerPassSlider.sync()

    const playerRewardsSlider = bricksSyncSliders('7a7095', 'c1f8ed')
    playerRewardsSlider.sync()

    const archiveHeroSlider = bricksSyncSliders('wfoabz', 'oeieah')
    archiveHeroSlider.sync()

    const subscriptionPlansSlider = bricksSyncSliders('hvncdm', 'wcrxzo')
    subscriptionPlansSlider.sync()

    const myGamesSlider = bricksSyncSliders('qrvcyr', 'ucsibe')
    myGamesSlider.sync()

    const featuredEarlyAccessSlider = bricksSyncSliders('uhsjsd', 'wxyovo')
    featuredEarlyAccessSlider.sync()

    // Archive Hero slider thumbnail navigation
    const archiveHeroSliderThumbs = document.getElementById('brxe-oeieah-track')?.getElementsByTagName('a')
    const archiveHeroMainSlider = bricksData?.splideInstances['wfoabz']
    if (archiveHeroSliderThumbs && archiveHeroSliderThumbs.length > 0) {
        for (let i = 0; i < archiveHeroSliderThumbs.length; i++) {
            archiveHeroSliderThumbs[i].addEventListener('click', function(e) {
                e.preventDefault()
                
                const thumbnailSlide = this.closest('.splide__slide')
                const isCurrentlyActive = thumbnailSlide?.classList.contains('is-active') && thumbnailSlide?.classList.contains('is-visible')
                
                if (isCurrentlyActive) {
                    if (handleGameRedirect(this)) {
                        return
                    }
                }
                
                updateSliderActiveState('wfoabz', i)
                
                if (archiveHeroMainSlider) {
                    archiveHeroMainSlider.go(i)
                }
            })
        }
    }

    // Archive Hero main slider click handlers
    const archiveHeroMainSlides = document.querySelectorAll('#wfoabz .splide__slide')
    if (archiveHeroMainSlides.length > 0) {
        archiveHeroMainSlides.forEach((slide, index) => {
            slide.addEventListener('click', function() {
                const isCurrentlyActive = slide.classList.contains('is-active') && slide.classList.contains('is-visible')
                
                if (isCurrentlyActive) {
                    if (handleGameRedirect(slide)) {
                        return
                    }
                }
                
                updateSliderActiveState('wfoabz', index)
                
                if (archiveHeroMainSlider) {
                    archiveHeroMainSlider.go(index)
                }
            })
        })
    }

    // Archive Hero slider event callbacks
    if (archiveHeroMainSlider && archiveHeroMainSlider.on) {
        archiveHeroMainSlider.on('moved', function(newIndex) {
            updateSliderActiveState('wfoabz', newIndex)
        })
    }

    // Featured Early Access slider thumbnail navigation
    const featuredEarlyAccessSliderThumbs = document.getElementById('brxe-wxyovo-track')?.getElementsByTagName('a')
    const featuredEarlyAccessMainSlider = bricksData?.splideInstances['uhsjsd']
    if (featuredEarlyAccessSliderThumbs && featuredEarlyAccessSliderThumbs.length > 0) {
        for (let i = 0; i < featuredEarlyAccessSliderThumbs.length; i++) {
            featuredEarlyAccessSliderThumbs[i].addEventListener('click', function(e) {
                e.preventDefault()
                
                const thumbnailSlide = this.closest('.splide__slide')
                const isCurrentlyActive = thumbnailSlide?.classList.contains('is-active') && thumbnailSlide?.classList.contains('is-visible')
                
                if (isCurrentlyActive) {
                    if (handleGameRedirect(this)) {
                        return
                    }
                }
                
                updateSliderActiveState('uhsjsd', i)
                
                if (featuredEarlyAccessMainSlider) {
                    featuredEarlyAccessMainSlider.go(i)
                }
            })
        }
    }

    // Game redirect handler
    const handleGameRedirect = function(element) {
        const gameUrl = element.getAttribute('href') || 
                       element.querySelector('a')?.getAttribute('href') ||
                       element.closest('a')?.getAttribute('href')
        
        if (gameUrl && gameUrl !== '#' && gameUrl !== 'javascript:void(0)') {
            window.location.href = gameUrl
            return true
        }
        return false
    }

    // Slider active state manager
    const updateSliderActiveState = function(sliderId, activeIndex) {
        document.querySelectorAll(`#${sliderId} .splide__slide.is-active`).forEach(active => {
            active.classList.remove('is-active', 'is-visible')
        })
        
        const targetSlide = document.querySelector(`#${sliderId} .splide__slide:nth-child(${activeIndex + 1})`)
        if (targetSlide) {
            targetSlide.classList.add('is-active', 'is-visible')
        }
    }

    // Featured Early Access main slider click handlers
    const featuredEarlyAccessMainSlides = document.querySelectorAll('#uhsjsd .splide__slide')
    if (featuredEarlyAccessMainSlides.length > 0) {
        featuredEarlyAccessMainSlides.forEach((slide, index) => {
            slide.addEventListener('click', function() {
                const isCurrentlyActive = slide.classList.contains('is-active') && slide.classList.contains('is-visible')
                
                if (isCurrentlyActive) {
                    if (handleGameRedirect(slide)) {
                        return
                    }
                }
                
                updateSliderActiveState('uhsjsd', index)
                
                if (featuredEarlyAccessMainSlider) {
                    featuredEarlyAccessMainSlider.go(index)
                }
            })
        })
    }

    // Featured Early Access slider event callbacks
    if (featuredEarlyAccessMainSlider && featuredEarlyAccessMainSlider.on) {
        featuredEarlyAccessMainSlider.on('moved', function(newIndex) {
            updateSliderActiveState('uhsjsd', newIndex)
        })
    }

    // Additional sliders configuration
    const sliderConfigs = [
        { mainId: 'zhiebz', name: 'Player Pass' },
        { mainId: '7a7095', name: 'Player Rewards' },
        { mainId: 'hvncdm', name: 'Subscription Plans' },
        { mainId: 'qrvcyr', name: 'My Games' }
    ]

    sliderConfigs.forEach(config => {
        const mainSlider = bricksData?.splideInstances[config.mainId]
        if (mainSlider) {
            const mainSlides = document.querySelectorAll(`#${config.mainId} .splide__slide`)
            if (mainSlides.length > 0) {
                mainSlides.forEach((slide, index) => {
                    slide.addEventListener('click', function() {
                        const isCurrentlyActive = slide.classList.contains('is-active') && slide.classList.contains('is-visible')
                        
                        if (isCurrentlyActive) {
                            if (handleGameRedirect(slide)) {
                                return
                            }
                        }
                        
                        updateSliderActiveState(config.mainId, index)
                        
                        if (mainSlider) {
                            mainSlider.go(index)
                        }
                    })
                })
            }

            if (mainSlider.on) {
                mainSlider.on('moved', function(newIndex) {
                    updateSliderActiveState(config.mainId, newIndex)
                })
            }
        }
    })


    setTimeout(() => {
        const instances = []
        instances.push(window.bricksData?.splideInstances['oqcvnm'])
        instances.push(window.bricksData?.splideInstances['nxdmvw'])
        instances.push(window.bricksData?.splideInstances['kzjcyl'])
        if (instances.length > 0) {
            for (let instance of instances) {
                if (instance) {
                    instance.destroy(true);
                    instance.mount({AutoScroll: window.splide.Extensions.AutoScroll})
                }
            }
        }
    }, 100)
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
                    console.log('User accepted the A2HS prompt')
                } else {
                    console.log('User dismissed the A2HS prompt')
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