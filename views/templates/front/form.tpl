{*
 * euwithdrawal - Step 1: the withdrawal form.
 * @license AFL-3.0
*}
<div id="euw-page" class="euw-page">
	<h1 class="page-heading">{$euw_title|escape:'html':'UTF-8'}</h1>

	{if $euw_intro}
		<div class="euw-intro rte">{$euw_intro nofilter}</div>
	{/if}

	{if $euw_errors}
		<div class="alert alert-danger">
			<ul class="euw-errors">
				{foreach from=$euw_errors item=err}
					<li>{$err|escape:'html':'UTF-8'}</li>
				{/foreach}
			</ul>
		</div>
	{/if}

	<form action="{$euw_action|escape:'html':'UTF-8'}" method="post" class="euw-form std" id="euw-form">
		<input type="hidden" name="euw_step" value="review" />
		{* honeypot - keep empty *}
		<div class="euw-hp" aria-hidden="true">
			<label>Website<input type="text" name="euw_website" value="" tabindex="-1" autocomplete="off" /></label>
		</div>

		<div class="row">
			<div class="col-xs-12 col-sm-6 form-group">
				<label for="euw_firstname">{l s='First name' mod='euwithdrawal'} <sup class="required">*</sup></label>
				<input type="text" class="form-control" id="euw_firstname" name="euw_firstname" value="{$euw_data.firstname|escape:'html':'UTF-8'}" required="required" />
			</div>
			<div class="col-xs-12 col-sm-6 form-group">
				<label for="euw_lastname">{l s='Last name' mod='euwithdrawal'} <sup class="required">*</sup></label>
				<input type="text" class="form-control" id="euw_lastname" name="euw_lastname" value="{$euw_data.lastname|escape:'html':'UTF-8'}" required="required" />
			</div>
		</div>

		<div class="row">
			<div class="col-xs-12 col-sm-6 form-group">
				<label for="euw_email">{l s='E-mail' mod='euwithdrawal'} <sup class="required">*</sup></label>
				<input type="email" class="form-control" id="euw_email" name="euw_email" value="{$euw_data.email|escape:'html':'UTF-8'}" required="required" />
			</div>
			<div class="col-xs-12 col-sm-6 form-group">
				<label for="euw_order_number">{l s='Order number' mod='euwithdrawal'} <sup class="required">*</sup></label>
				<input type="text" class="form-control" id="euw_order_number" name="euw_order_number" value="{$euw_data.order_number|escape:'html':'UTF-8'}" required="required"{if $euw_orders} list="euw_orders_list"{/if} />
				{if $euw_orders}
					<datalist id="euw_orders_list">
						{foreach from=$euw_orders item=o}
							<option value="{$o.reference|escape:'html':'UTF-8'}">{$o.label|escape:'html':'UTF-8'}</option>
						{/foreach}
					</datalist>
				{/if}
			</div>
		</div>

		<div class="row">
			<div class="col-xs-12 col-sm-6 form-group">
				<label for="euw_date_received">{l s='Date goods received' mod='euwithdrawal'} <span class="euw-optional">({l s='optional' mod='euwithdrawal'})</span></label>
				<input type="date" class="form-control" id="euw_date_received" name="euw_date_received" value="{$euw_data.date_received|escape:'html':'UTF-8'}" />
			</div>
		</div>

		<div class="form-group">
			<label for="euw_reason">{l s='Reason' mod='euwithdrawal'} <span class="euw-optional">({l s='optional, not required by law' mod='euwithdrawal'})</span></label>
			<textarea class="form-control" id="euw_reason" name="euw_reason" rows="3">{$euw_data.reason|escape:'html':'UTF-8'}</textarea>
		</div>

		<div class="euw-actions">
			<button type="submit" name="euw_continue" value="1" class="btn btn-default button button-medium">
				<span>{l s='Continue' mod='euwithdrawal'} <i class="icon-chevron-right right"></i></span>
			</button>
		</div>
	</form>
</div>
