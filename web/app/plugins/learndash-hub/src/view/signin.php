<div class="wrap learndash-hub">
	<div aria-live="assertive" id="error" class="absolute hidden inset-0 mt-8 flex items-end px-4 py-6 pointer-events-none sm:p-6 sm:items-start">
		<div class="w-full flex flex-col items-center space-y-8 sm:items-end">
			<div class="max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
				<div class="p-4">
					<div class="flex items-start">
						<div class="flex-shrink-0">
							<!-- Heroicon name: outline/check-circle -->
							<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
								<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
							</svg>
						</div>
						<div class="ml-3 w-0 flex-1 pt-0.5">
							<p class="text-sm font-medium text-gray-900">Something wrong.</p>
							<p class="mt-1 text-sm text-gray-500 error-message">
							</p>
						</div>
						<div class="ml-4 flex-shrink-0 flex">
							<button class="bg-white close-error rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
								<span class="sr-only">Close</span>
								<!-- Heroicon name: solid/x -->
								<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
									<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
								</svg>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="grid place-items-center" style="height: 80vh">
		<div class="w-2/5">
			<h3 class="text-2xl tracking-tight font-semibold text-center mb-4">
				<?php esc_html_e( 'Welcome to LearnDash!', 'learndash-hub' ); ?>
			</h3>
			<p class="text-base mb-2 text-center mb-8">
				<?php
				esc_html_e(
					'We know you are excited to get started, but before you do it is very important that you first add your license details below!',
					'learndash-hub'
				);
				?>
			</p>
			<form class="w-full mb-8" id="license">
				<div class="mb-6">
					<input class="block" style="max-width: 100%" placeholder="Email" name="email" id="email" type="text"/>
				</div>
				<div class="mb-6">
					<input style="max-width: 100%" id="license_key" name="license_key" type="text" placeholder="License Key">
				</div>
				<input type="hidden" name="action" value="ld_hub_verify_and_save_license"/>
				<?php wp_nonce_field( 'ld_hub_verify_license', 'hubnonce' ); ?>
				<div class="text-center">
					<button class="hub-button blue" type="submit">
						<i class="fa-spinner fas fa-spin mr-2"></i>
						<i class="fas fa-save mr-2"></i>
						<span class="text"><?php esc_html_e( 'Save License', 'learndash-hub' ); ?></span>
					</button>
				</div>
			</form>
			<ul class="list-disc absolute left-auto bottom-0 text-gray-500">
				<li class="text-sm">
					<?php
					esc_html_e(
						'Your active license gives you access to product support and updates that we push out.',
						'learndash-hub'
					);

					?>
				</li>
				<li class="text-sm">
					<?php esc_html_e( 'Your license details were emailed to you after purchase.', 'learndash-hub' ); ?>
				</li>
				<li class="text-sm">
					<?php
					printf(
						__(
							'You can also find them listed <a class="text-blue-500" target="_blank" href="%s">on your account.</a>',
							'learndash-hub'
						),
						'https://support.learndash.com/wp-login.php'
					);
					?>
				</li>
			</ul>
		</div>
	</div>
</div>
<script type="text/javascript">
	jQuery(function ($) {
		$('#license').on('submit', function (e) {
			e.preventDefault();
			let that = $(this)
			that.find('button').removeClass('blue').addClass('loading')
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: that.serialize(),
				success: function (data) {
					that.find('button').removeClass('loading').addClass('blue');
					if (data.success === false) {
						$('.error-message').text(data.data)
						$('#error').removeClass('hidden')
					} else {
						location.reload()
					}
				}
			})
		})
		$('.close-error').on('click',function (){
			$('#error').addClass('hidden')
		})
	})
</script>
