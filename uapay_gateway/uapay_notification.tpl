
{if $status == 'success'}
    <p>{l s='Your order ' mod='uapay'} <span class="bold">{$shop_name}</span> {l s='successfully paid.' mod='uapay'}
        <br /><br /><span class="bold">{l s='Your order will be delivered as quickly as possible.' mod='uapay'}</span>
        <br /><br />{l s='In case of any questions please contact us' mod='uapay'} <a href="{$link->getPageLink('contact', true)}">{l
            s='Customer Support' mod='uapay'}</a>.
        <br /><br />{l s='You can view your ' mod='uapay'} <a href="{$link->getPageLink('history', true)}">{l
            s='order history' mod='uapay'}</a>.
    </p>
    {else}
    {if $status == 'waitAccept'}
    <p>{l s='Your order' mod='uapay'} <span class="bold">{$shop_name}</span> {l s='awaiting payment.' mod='uapay'}
        <br /><br /><span class="bold">{l s='Your order will be delivered as quickly as possible after the receipt after payment.'
            mod='uapay'}</span>
        <br /><br />{l s='In case of any questions please contact us' mod='uapay'} <a href="{$link->getPageLink('contact', true)}">{l s='Customer Support' mod='uapay'}</a>.
    </p>
    {else}
    <p class="canceled">
        {l s='Your order has not been paid. If you think that this was an error, please contact us.' mod='uapay'}
        <a href="{$link->getPageLink('contact', true)}">{l s='Customer Support' mod='uapay'}</a>.
    </p>
    {/if}
{/if}
