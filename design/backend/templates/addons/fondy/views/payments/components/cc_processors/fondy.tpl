{assign var="statuses" value=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses}
{assign var="currency" value=$smarty.const.CURRENCY|fn_get_currencies_list}
{assign var="language" value=$smarty.const.LANGUAGE|fn_get_simple_languages}

<div class="control-group">
    <label class="control-label" for="test_mode">{__("addons.fondy.test_mode")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][test_mode]" id="test_mode">
            <option value="no"{if $processor_params.test_mode == "no"} selected="selected"{/if}>{__("No")}</option>
            <option value="yes"{if $processor_params.test_mode == "yes"} selected="selected"{/if}>{__("Yes")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="merchant_id">{__("addons.fondy.merchant_id")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][merchant_id]" id="merchant_id"
               value="{$processor_params.merchant_id}" class="input-text"/>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="password">{__("addons.fondy.password")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][password]" id="password"
               value="{$processor_params.password}" class="input-text" size="100"/>
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="language">{__("addons.fondy.language")}</label>
    <div class="controls">
        <select name="payment_data[processor_params][language]" id="language">
            {foreach from=$language item="s" key="k"}
                <option value="{$k}" {if $processor_params.language == $k}selected="selected"{/if}>{$s}</option>
            {/foreach}
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="payment_type">{__("addons.fondy.payment_type")}</label>
    <div class="controls">
        <select name="payment_data[processor_params][payment_type]" id="payment_type">
            <option value="redirect"{if $processor_params.payment_type == "redirect"} selected="selected"{/if}>{__("Redirect to Fondy")}</option>
            {*            <option value="sale"{if $processor_params.payment_type == "sale"} selected="selected"{/if}>{__("Sale")}</option>*}
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="transaction_method">{__("addons.fondy.transaction_method")}</label>
    <div class="controls">
        <select name="payment_data[processor_params][transaction_method]" id="transaction_method">
            <option value="sale"{if $processor_params.transaction_method == "sale"} selected="selected"{/if}>{__("Sale")}</option>
            <option value="hold"{if $processor_params.transaction_method == "hold"} selected="selected"{/if}>{__("Hold")}</option>
        </select>
    </div>
</div>

<div class="control-group" id="row_status_hold">
    <label class="control-label" for="status_hold">{__("addons.fondy.status_hold")}</label>
    <div class="controls">
        <select name="payment_data[processor_params][status_hold]" id="status_hold">
            {foreach from=$statuses item="s" key="k"}
                <option value="{$k}" {if $processor_params.status_hold == $k}selected="selected"{/if}>{$s}</option>
            {/foreach}
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="currency">{__("addons.fondy.currency")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][currency]" id="currency">
            {foreach from=$currency item="s" key="k"}
                <option value="{$k}" {if $processor_params.currency == $k}selected="selected"{/if}>{$k}</option>
            {/foreach}
        </select>
    </div>
</div>

<script type="text/javascript">
    (function (_, $) {
        $(document).ready(function () {
            $('#test_mode').change(function () {
                if ($(this).val() == 'yes') {
                    $('#merchant_id').val('1396424');
                    $('#password').val('test');
                } else {
                    $('#merchant_id').val('');
                    $('#password').val('');
                }
            });
            $('#merchant_id, #password').change(function () {
                $('#test_mode').val('no');
            });
            $('#transaction_method').change(function () {
                if ($(this).val() == 'hold') {
                    $('#row_status_hold').show();
                } else {
                    $('#row_status_hold').hide();
                }
            });
        });
    }(Tygh, Tygh.$));
</script>