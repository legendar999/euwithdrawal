{*
 * euwithdrawal - Step 3: acknowledgement of receipt.
 * @license AFL-3.0
*}
<div id="euw-page" class="euw-page euw-done">
	<h1 class="page-heading">{$euw_title|escape:'html':'UTF-8'}</h1>

	<div class="alert alert-success euw-done-box">
		<p class="euw-done-main"><i class="icon-check"></i> {l s='We have received your withdrawal from the contract.' mod='euwithdrawal'}</p>
		<p>
			{l s='An acknowledgement of receipt has been sent to' mod='euwithdrawal'}
			<strong>{$euw_email|escape:'html':'UTF-8'}</strong>.
		</p>
		<p class="euw-muted">
			{l s='Reference' mod='euwithdrawal'}: {$euw_reference|escape:'html':'UTF-8'}
			&nbsp;|&nbsp; {l s='Request no.' mod='euwithdrawal'}: {$euw_request_id|intval}
		</p>
		<p>{l s='You will be informed about the next steps.' mod='euwithdrawal'}</p>
	</div>

	<p class="euw-actions">
		<a href="{$euw_home|escape:'html':'UTF-8'}" class="btn btn-default button button-medium">
			<span><i class="icon-chevron-left left"></i> {l s='Back to the shop' mod='euwithdrawal'}</span>
		</a>
	</p>
</div>
