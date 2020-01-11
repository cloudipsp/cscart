{if $sendLink != ''}
    <a href="{$sendLink}">Send payment link</a>
    {if $error['message'] != ''}
        <p>{$error['message']}, {$error['request_id']}</p>
    {/if}
{/if}