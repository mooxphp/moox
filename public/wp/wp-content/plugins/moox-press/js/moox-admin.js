(function updateBackLinkAndLogo() {
    const backLink = document.querySelector(".edit-post-fullscreen-mode-close");
    const postType = getQueryParam("post_type");

    if (backLink) {
        let backLinkUrl = getFallbackUrl(postType);
        const referrer = document.referrer;
        const isInternalReferrer =
            referrer && new URL(referrer).hostname === window.location.hostname;

        if (isInternalReferrer) {
            backLinkUrl = referrer;
        }

        backLink.href = backLinkUrl;
        backLink.setAttribute("aria-label", "Back to Moox");
        backLink.title = "Back to Moox";

        const logoSVG = `<svg version="1.0" xmlns="http://www.w3.org/2000/svg"
        width="500.000000pt" height="500.000000pt" viewBox="0 0 500.000000 500.000000"
        preserveAspectRatio="xMidYMid meet">
       <g transform="translate(0.000000,500.000000) scale(0.100000,-0.100000)"
       fill="#FFFFFF" stroke="none">
       <path d="M0 2500 l0 -2500 2500 0 2500 0 0 2500 0 2500 -2500 0 -2500 0 0
       -2500z m1503 1065 c15 -1 30 -5 34 -8 3 -4 16 -7 28 -7 22 0 190 -64 210 -79
       5 -5 21 -12 34 -16 13 -4 42 -20 65 -35 22 -16 45 -26 51 -23 5 4 7 2 2 -2 -4
       -5 12 -20 35 -33 24 -14 54 -35 68 -47 14 -12 33 -26 42 -31 32 -17 221 -199
       295 -282 43 -50 73 -93 73 -106 -1 -31 -206 -296 -230 -296 -11 0 -53 39 -104
       98 -183 209 -365 346 -546 410 -67 23 -90 26 -210 26 -124 1 -141 -2 -205 -27
       -134 -53 -247 -148 -318 -268 -56 -96 -79 -176 -84 -304 -6 -128 10 -214 58
       -311 36 -72 132 -194 154 -194 8 0 15 -4 15 -9 0 -5 12 -12 26 -15 15 -4 33
       -14 42 -22 8 -9 25 -14 38 -11 13 2 21 0 18 -4 -3 -5 5 -9 18 -9 13 0 48 -10
       78 -22 72 -29 225 -31 315 -4 33 10 68 18 78 17 9 0 15 4 12 9 -3 4 4 6 14 3
       11 -3 30 2 43 11 12 9 30 16 40 16 10 0 18 5 18 11 0 5 4 7 10 4 9 -6 18 0 54
       32 8 7 17 9 20 6 4 -4 18 5 32 20 14 15 28 27 33 27 4 0 32 23 61 50 29 28 56
       50 59 50 9 0 90 80 157 156 89 100 142 166 279 349 145 195 213 274 359 420
       61 61 110 114 108 118 -1 5 2 6 7 3 5 -4 15 0 23 7 7 7 34 28 61 47 26 18 47
       37 47 42 0 4 7 8 15 8 9 0 18 7 21 15 4 8 11 15 16 15 6 0 23 11 40 25 16 14
       36 25 44 25 9 0 13 3 10 8 -2 4 15 16 37 26 23 10 51 23 61 28 79 41 276 86
       391 89 130 4 299 -29 369 -72 17 -10 39 -19 49 -19 9 0 17 -4 17 -10 0 -5 5
       -10 12 -10 29 0 197 -131 279 -216 140 -148 219 -294 271 -503 18 -70 22 -115
       21 -241 0 -135 -3 -168 -26 -253 -54 -202 -136 -347 -276 -488 -307 -306 -738
       -380 -1144 -195 -102 46 -253 147 -357 238 -116 101 -280 278 -280 302 0 10
       31 62 69 115 37 53 77 110 87 126 50 82 96 87 142 18 34 -53 219 -243 236
       -243 6 0 17 -9 24 -20 7 -11 18 -20 25 -20 6 0 28 -14 47 -30 19 -16 40 -30
       47 -30 7 0 32 -11 56 -24 27 -15 52 -23 66 -19 11 3 28 -1 37 -8 9 -7 21 -14
       28 -16 6 -1 44 -11 85 -23 41 -11 100 -20 131 -20 49 0 145 18 190 36 8 4 28
       8 44 9 15 2 40 8 55 15 161 75 232 150 294 310 39 99 48 249 23 364 -53 234
       -246 435 -463 481 -313 67 -597 -79 -944 -485 -31 -36 -114 -141 -184 -235
       -298 -395 -466 -572 -690 -727 -347 -241 -740 -281 -1080 -111 -185 93 -356
       266 -452 458 -75 148 -107 288 -108 465 0 242 66 454 200 640 46 64 230 250
       247 250 5 0 11 7 14 15 4 8 15 15 25 15 11 0 22 6 25 13 4 12 104 67 159 87
       82 31 124 42 206 54 81 12 134 12 272 1z"/>
       </g>
       </svg>`;
        backLink.innerHTML = logoSVG + backLink.innerHTML;
    } else {
        setTimeout(updateBackLinkAndLogo, 100);
    }
})();

function getFallbackUrl(postType) {
    return `/admin/wp-${postType}s`;
}

function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}
