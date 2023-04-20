/* global Beacon */

jQuery(function ($) {
	$(document).on('click', '.box .content', function (e) {
		const $box = $(this).closest('.box'),
			url = $box.data('url'),
			completed = $box.data('completed') === 1;

		if (url && url.length > 0 && !completed) {
			e.preventDefault();
			window.location.href = url;
		}
	});

	$(document).on(
		'click',
		'a[data-type="article"], a[data-type="overview_article"]',
		function (e) {
			e.preventDefault();
			Beacon('article', $(this).data('helpscout_id'), { type: 'modal' });
		}
	);

	$(document).on('click', 'a[data-type="helpscout_action"]', function (e) {
		e.preventDefault();

		const action = $(this).data('action');
		let keyword, articles;

		switch (action) {
			case 'open_doc':
				keyword = $(this).data('keyword');

				if (keyword.length > 0) {
					Beacon('navigate', '/docs/search?query=' + keyword);
				} else {
					Beacon('suggest', []);
					Beacon('navigate', '/answers/');
				}

				Beacon('open');
				break;

			case 'open_chat':
				Beacon('navigate', '/ask/');
				Beacon('open');
				break;

			case 'suggest_articles':
				articles = $(this).data('articles').split(',');

				Beacon('suggest', articles);
				Beacon('navigate', '/answers/');
				Beacon('open');
				break;
		}
	});

	$(document).on('click', '[data-type="youtube_video"]', function (e) {
		e.preventDefault();

		const youtubeId = $(this).data('youtube_id'),
			src =
				'https://www.youtube.com/embed/' +
				youtubeId +
				'?autoplay=1&controls=1';

		$('.video-wrapper .video-iframe').attr('src', src);
		$('.video-wrapper').show();
	});

	$(document).on('click', '[data-type="vimeo_video"]', function (e) {
		e.preventDefault();

		const vimeoId = $(this).data('vimeo_id'),
			src = 'https://player.vimeo.com/video/' + vimeoId;

		$('.video-wrapper .video-iframe').attr('src', src);
		$('.video-wrapper').show();
	});

	$(document).on('click', '.video-wrapper .close', function (e) {
		e.preventDefault();

		$('.video-wrapper .video-iframe').removeAttr('src');
		$('.video-wrapper').hide();
	});
});
