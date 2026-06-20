{*
 * euwithdrawal - small info panel above the configuration form.
 * @license AFL-3.0
*}
<div class="panel">
	<div class="panel-heading"><i class="icon-info-circle"></i> {l s='EU Withdrawal' mod='euwithdrawal'}</div>
	<div class="euw-config-info">
		<p>
			{l s='Public withdrawal page:' mod='euwithdrawal'}
			<a href="{$euw_public_url|escape:'html':'UTF-8'}" target="_blank" rel="noopener">{$euw_public_url|escape:'html':'UTF-8'}</a>
		</p>
		<p>
			{l s='Requests received so far:' mod='euwithdrawal'} <strong>{$euw_total|intval}</strong>
			&nbsp;&middot;&nbsp;
			<a href="{$euw_admin_url|escape:'html':'UTF-8'}">{l s='Open the register' mod='euwithdrawal'}</a>
		</p>
		<p class="text-muted">
			{l s='The footer link uses the displayFooter hook. If your theme hides it, enable the header or floating button instead.' mod='euwithdrawal'}
		</p>
	</div>
</div>
