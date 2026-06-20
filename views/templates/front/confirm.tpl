{*
 * euwithdrawal - Step 2: review & confirm (the legally required "second click").
 * @license AFL-3.0
*}
<div id="euw-page" class="euw-page euw-confirm">
	<h1 class="page-heading">{$euw_title|escape:'html':'UTF-8'}</h1>

	<p class="euw-review-lead">{l s='Please review your details and confirm the withdrawal.' mod='euwithdrawal'}</p>

	<div class="euw-summary">
		<dl class="euw-dl">
			<dt>{l s='Name' mod='euwithdrawal'}</dt>
			<dd>{$euw_data.firstname|escape:'html':'UTF-8'} {$euw_data.lastname|escape:'html':'UTF-8'}</dd>
			<dt>{l s='E-mail' mod='euwithdrawal'}</dt>
			<dd>{$euw_data.email|escape:'html':'UTF-8'}</dd>
			<dt>{l s='Order' mod='euwithdrawal'}</dt>
			<dd>{$euw_order.reference|escape:'html':'UTF-8'} <span class="euw-muted">({l s='placed' mod='euwithdrawal'} {$euw_order.date_add|escape:'html':'UTF-8'})</span></dd>
			{if $euw_data.date_received}
				<dt>{l s='Date goods received' mod='euwithdrawal'}</dt>
				<dd>{$euw_data.date_received|escape:'html':'UTF-8'}</dd>
			{/if}
			{if $euw_data.reason}
				<dt>{l s='Reason' mod='euwithdrawal'}</dt>
				<dd>{$euw_data.reason|escape:'html':'UTF-8'}</dd>
			{/if}
		</dl>
	</div>

	<form action="{$euw_action|escape:'html':'UTF-8'}" method="post" class="euw-form" id="euw-confirm-form">
		<input type="hidden" name="euw_step" value="confirm" />
		<input type="hidden" name="euw_token" value="{$euw_token|escape:'html':'UTF-8'}" />
		<input type="hidden" name="euw_firstname" value="{$euw_data.firstname|escape:'html':'UTF-8'}" />
		<input type="hidden" name="euw_lastname" value="{$euw_data.lastname|escape:'html':'UTF-8'}" />
		<input type="hidden" name="euw_email" value="{$euw_data.email|escape:'html':'UTF-8'}" />
		<input type="hidden" name="euw_order_number" value="{$euw_data.order_number|escape:'html':'UTF-8'}" />
		<input type="hidden" name="euw_date_received" value="{$euw_data.date_received|escape:'html':'UTF-8'}" />
		<input type="hidden" name="euw_reason" value="{$euw_data.reason|escape:'html':'UTF-8'}" />
		<div class="euw-hp" aria-hidden="true">
			<label>Website<input type="text" name="euw_website" value="" tabindex="-1" autocomplete="off" /></label>
		</div>

		{if $euw_allow_items && $euw_products}
			<div class="euw-scope">
				<p class="euw-scope-title">{l s='What do you withdraw from?' mod='euwithdrawal'}</p>
				<label class="euw-radio">
					<input type="radio" name="euw_scope" value="order" checked="checked" class="euw-scope-radio" />
					{l s='The whole order' mod='euwithdrawal'}
				</label>
				<label class="euw-radio">
					<input type="radio" name="euw_scope" value="items" class="euw-scope-radio" />
					{l s='Only selected items' mod='euwithdrawal'}
				</label>

				{* Visible by default for no-JS users; front.js hides it while "whole order" is selected. *}
				<div class="euw-items" id="euw-items">
					{foreach from=$euw_products item=p}
						<label class="euw-item">
							<input type="checkbox" name="euw_items[]" value="{$p.id_order_detail|intval}" />
							{$p.name|escape:'html':'UTF-8'} <span class="euw-muted">x{$p.qty|intval} - {$p.price|escape:'html':'UTF-8'}</span>
						</label>
					{/foreach}
				</div>
			</div>
		{/if}

		<div class="euw-actions">
			<button type="submit" name="euw_back" value="1" class="btn btn-default button button-medium euw-back">
				<span><i class="icon-chevron-left left"></i> {l s='Back' mod='euwithdrawal'}</span>
			</button>
			<button type="submit" name="euw_submit" value="1" class="btn btn-primary button button-medium euw-confirm-btn">
				<span>{l s='Confirm withdrawal' mod='euwithdrawal'}</span>
			</button>
		</div>
	</form>
</div>
