/* global Beacon */

// Start Helpscout Beacon

/* eslint-disable */
!function(e,t,n){function a(){var e=t.getElementsByTagName("script")[0],n=t.createElement("script");n.type="text/javascript",n.async=!0,n.src="https://beacon-v2.helpscout.net",e.parentNode.insertBefore(n,e)}if(e.Beacon=n=function(t,n,a){e.Beacon.readyQueue.push({method:t,options:n,data:a})},n.readyQueue=[],"complete"===t.readyState)return a();e.attachEvent?e.attachEvent("onload",a):e.addEventListener("load",a,!1)}(window,document,window.Beacon||function(){});
/* eslint-enable */

window.Beacon('init', '1418fe60-cd03-4691-a765-66e6166f1695');

// End Helpscout Beacon

(function () {
	jQuery(function ($) {
		$(document).on('submit', '#search-form', function (e) {
			e.preventDefault();

			const inputs = {};

			$.each($(this).serializeArray(), function (key, field) {
				inputs[field.name] = field.value;
			});

			if (inputs.keyword.length > 0) {
				Beacon('open');
				Beacon('search', inputs.keyword);
			}
		});

		$(document).on('click', '.answers .item', function (e) {
			e.preventDefault();

			const id = $(this).data('id');

			Beacon('open');
			Beacon('navigate', '/docs/search?query=category:' + id);
		});

		Beacon('on', 'ready', function () {
			$('body').append('<div class="beacon-background"></div>');
		});

		Beacon('on', 'article-viewed', function () {
			$('body').addClass('beacon-open');

			setTimeout(function () {
				const intervalId = setInterval(function () {
					const frame = $(
						'.beacon-open .Beacon #BeaconInlineArticlesFrame, .Beacon .BeaconContainer-enter-done'
					);

					if (frame.length < 1) {
						$('body').removeClass('beacon-open');

						clearInterval(intervalId);
					} else {
						$('body').addClass('beacon-open');
					}
				}, 200);
			}, 300);
		});

		Beacon('on', 'open', function () {
			$('body').addClass('beacon-open');
		});

		Beacon('on', 'close', function () {
			$('body').removeClass('beacon-open');
		});
	});
})();
