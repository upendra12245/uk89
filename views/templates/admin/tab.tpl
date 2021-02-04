{*
* 2007-2019 PrestaShop SA and Contributors
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* https://opensource.org/licenses/AFL-3.0
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
* @author    PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2019 PrestaShop SA and Contributors
* @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}

{*
{$var|@debug_print_var}
<hr>
*}
<div class="panel" >
    <div class="row h-100">
        <div class="col-md-7 my-auto">
            <label class="form-control-label" for="simple_product">
                {l s='Sticker Status : ' mod='promostickers'}
            </label>
            <span class="help-box" data-toggle="popover" data-content="{l s='Turn ON/OFF PromoSticker for this product' mod='promostickers'}"></span>
            <select name="promo_status" class="form-control">
                <option value="1" {if ($sticker[0]['promo_status'] == 1)} selected {else} {/if}>{l s='ON' mod='promostickers'}</option>
                <option value="0" {if ($sticker[0]['promo_status'] == 1)} {else} selected {/if}>{l s='OFF' mod='promostickers'}</option>
            </select>
        </div>

        <div class="col-md-5 my-auto">
            <div class="alert alert-info mt-md-4">
            <label class="form-control-label">
                <p class="alert-text">
                {l s='Don\'t forget to select img\'s types in ' mod='promostickers'}
                <a href="{$module|escape:'htmlall':'UTF-8'}" class="" ><i class="form-control-static mt-md-3"></i> {l s='module settings' mod='promostickers'}</a>
                </p>
            </label>
            </div>
        </div>
    </div>
		
    <hr>
		
		
    <div class="row h-100">
        <div class="col-md-7 my-auto">
		
            <div class="row">
                <div class="col-md-12">
                    <label class="form-control-label">
                        {l s='Image :' mod='promostickers'}
                    </label>
                    <span class="help-box" data-toggle="popover" data-content="You can upload additional images in Modul settings"></span>
                    <select name="promo_img" class="form-control" id="promo_img">
                        <option value="" data-imagesrc="" data-description="">Select img or leave empty</option>
                        {foreach key=cid item=files from=$stickerslist}
                            <option value="{$files.img|escape:'htmlall':'UTF-8'}" data-imagesrc="{if !empty($files.picture)} {$files.picture|escape:'htmlall':'UTF-8'} {else} {/if}" {if ($sticker[0]['promo_img'] == $files.img)} selected {else} {/if}>{if !empty($files.img)} {$files.img|escape:'htmlall':'UTF-8'} {else} Select img or leave empty {/if}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <br>

            <div class="row">
                <div class="col-md-6">
                    <label class="form-control-label">
                        {l s='Align vertical :' mod='promostickers'}
                    </label>
                    <span class="help-box" data-toggle="popover" data-content="{l s='This setting is available in the Pro Version' mod='promostickers'}"></span>
                    <span data-toggle="popover" data-content="{l s='This setting is available in the Pro Version' mod='promostickers'}">
                    <select name="promo_img_vertical" class="form-control" readonly>
                        <option value="-1">{l s='Top' mod='promostickers'}</option>
                        <option value="0" >{l s='Center' mod='promostickers'}</option>
                        <option value="1" selected >{l s='Bottom' mod='promostickers'}</option>
                    </select></span>
                </div>

                <div class="col-md-6">
                    <label class="form-control-label">
                        {l s='Align horizontal :' mod='promostickers'}
                    </label>
                    <span class="help-box" data-toggle="popover" data-content="{l s='This setting is available in the Pro Version' mod='promostickers'}"></span>
                    <span data-toggle="popover" data-content="{l s='This setting is available in the Pro Version' mod='promostickers'}">
                    <select name="promo_img_horizontal" class="form-control" readonly>
                        <option value="-1">{l s='Left' mod='promostickers'}</option>
                        <option value="0" selected >{l s='Center' mod='promostickers'}</option>
                        <option value="1">{l s='Right' mod='promostickers'}</option>
                    </select></span>
                </div>
            </div>
		</div>

        <div class="col-md-5 my-auto">
            <div class="alert alert-info mt-md-4">
                <img src="{$manual_img1|escape:'htmlall':'UTF-8'}" class="img-fluid img-responsive">
            </div>
        </div>

	</div>
		
    <hr>

	<div class="row h-100">
		<div class="col-md-7 my-auto">
		
            <div class="row">
                <div class="col-md-6">
                    <label class="form-control-label">
                        {l s='Text : ' mod='promostickers'}
                    </label>
                    <span class="help-box" data-toggle="popover" data-content="{l s='Short text will be displayed on sticker. Or leave this field empty to use sticker without text' mod='promostickers'}"></span>
                    <input type="text" name="promo_txt" value="{$sticker[0]['promo_txt']|escape:'htmlall':'UTF-8'}" placeholder="{l s='Short text or leave empty' mod='promostickers'}" class="form-control" />
                </div>

                <div class="col-md-6">
                    <label class="form-control-label">
                        {l s='Font of text :' mod='promostickers'}
                    </label>
                    <span class="help-box" data-toggle="popover" data-content="You can upload additional fonts in TTF format to directory ../module/promostickers/views/fonts/ "></span>
                    <select name="promo_txt_font" class="form-control">
                        {foreach key=cid item=files from=$fontslist}
                            <option value="{$files.font|escape:'htmlall':'UTF-8'}"  {if ($sticker[0]['promo_txt_font'] == $files.font)} selected {else} {/if}>{$files.font|escape:'htmlall':'UTF-8'}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-control-label">
                        {l s='Text size :' mod='promostickers'}
                    </label>
                    <span class="help-box" data-toggle="popover" data-content="{l s='This setting is available in the Pro Version' mod='promostickers'}"></span>
                    <span data-toggle="popover" data-content="{l s='This setting is available in the Pro Version' mod='promostickers'}">
                    <select name="promo_txt_size" class="form-control" readonly>
                        <option value="10" >{l s='Small' mod='promostickers'}</option>
                        <option value="50" selected >{l s='Medium' mod='promostickers'}</option>
                        <option value="100" >{l s='Large' mod='promostickers'}</option>
                    </select></span>
                </div>

                <div class="col-md-6">
                    <label class="form-control-label">
                        {l s='Text color :' mod='promostickers'}
                    </label>
                    <input type="color" name="promo_txt_color" value="{if ($sticker[0]['promo_txt_color'])}{$sticker[0]['promo_txt_color']|escape:'htmlall':'UTF-8'}{else}#FFFFFF{/if}" class="col" />
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-control-label" for="input-promo_shadow">
                        {l s='Text shadow :' mod='promostickers'}
                    </label>
                    <select name="promo_txt_shadow" class="form-control">
                        <option value="1" {if (!isset($sticker[0]['promo_txt_shadow']) || ($sticker[0]['promo_txt_shadow'] == 1))} selected {else} {/if}>{l s='ON' mod='promostickers'}</option>
                        <option value="0" {if (isset($sticker[0]['promo_txt_shadow']) && ($sticker[0]['promo_txt_shadow'] == 0))} selected {else} {/if}>{l s='OFF' mod='promostickers'}</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-control-label">
                        {l s='Align text horizontal :' mod='promostickers'}
                    </label>
                    <input type="text" name="promo_txt_horizontal" value="{if ($sticker[0]['promo_txt_horizontal'])}{$sticker[0]['promo_txt_horizontal']|escape:'htmlall':'UTF-8'}{else}15{/if}" class="form-control" />
                </div>
            </div>
		</div>

        <div class="col-md-5 my-auto">
            <div class="alert alert-info mt-md-4">
                <img src="{$manual_img2|escape:'htmlall':'UTF-8'}" class="img-fluid img-responsive"/>
            </div>
        </div>
		
	</div>

    <hr>
    <p><h3>
        <i class="icon icon-exclamation-circle"></i>
        <strong>All features are available in <a href="https://tobiksoft.com/prestashop/22-promotional-stickers-pro-module-for-prestashop">the PRO VERSION </a></strong></h3>
    </p>
    <div class="panel-footer ">
        <a href="{if $ver == 1}{$link->getAdminLink('AdminProducts')|escape:'htmlall':'UTF-8'} {else}{$cancel|escape:'htmlall':'UTF-8'} {/if}" class="btn {if $ver == 1}btn-primary {else}btn-default{/if} " ><i class="process-icon-cancel"></i> {l s='Cancel' mod='promostickers'}</a>
        <button type="submit" name="submitAddproduct" class="btn {if $ver == 1}btn-primary {else}btn-default{/if}  pull-right "> <i class="process-icon-save"></i> {l s='Save' mod='promostickers'}</button>
        {if $ver !== 1}<button type="submit" name="submitAddproductAndStay" class="btn {if $ver == 1}btn-primary {else}btn-default{/if} pull-right "><i class="process-icon-save"></i>{l s='Save and stay' mod='promostickers'}</button>{else} {/if}
    </div>

</div>
