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

    const archiveHeroSliderThumbs = document.getElementById('brxe-oeieah-track')?.getElementsByTagName('a')
    const archiveHeroMainSlider = bricksData?.splideInstances['wfoabz']
    if (archiveHeroSliderThumbs && archiveHeroSliderThumbs.length > 0) {
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
                if (archiveHeroMainSlider) {
                    archiveHeroMainSlider.go(i)
                }
            })
        }
    }

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
        backElm.href = window.location.origin;
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
