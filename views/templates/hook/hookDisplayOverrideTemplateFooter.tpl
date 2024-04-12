{extends file='checkout/checkout.tpl'}

{block name='hook_before_body_closing_tag'}
  <div class="modal fade js-checkout-modal" id="modal">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' d='Shop.Theme.Global'}">
          <span aria-hidden="true">&times;</span>
        </button>
        <div class="js-modal-content"></div>
      </div>
    </div>
  </div>
  {hook h='displayBeforeBodyClosingTag'}
{/block}