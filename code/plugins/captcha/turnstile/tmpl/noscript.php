<?php
/**
 * @copyright   (C) 2022 SharkyKZ
 * @license     GPL-3.0-or-later
 */

defined('_JEXEC') || exit;

/** @var PlgCaptchaTurnstile $this */

?>
<noscript>
	<div class="alert alert-warning">
		<?php echo $this->app->getLanguage()->_('PLG_CAPTCHA_TURNSTILE_NOSCRIPT'); ?>
	</div>
</noscript>
