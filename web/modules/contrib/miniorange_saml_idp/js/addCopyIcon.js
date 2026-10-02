function CopyToClipboard(element){
    jQuery(".selected-text").removeClass("selected-text");
    let textToCopy = document.getElementById('saml_idp_metadeta_url').innerText;
    navigator.clipboard.writeText(textToCopy);
    jQuery(element).addClass("selected-text");
}

jQuery(window).click(function(e) {
    if( e.target.className === undefined || e.target.className.indexOf("copy_button") === -1)
        jQuery(".selected-text").removeClass("selected-text");
});

function CopyMetaToClipboard(element){
    jQuery(".selected-text").removeClass("selected-text");
    let textToCopy = element.innerText;
    navigator.clipboard.writeText(textToCopy);
    jQuery(element).addClass("selected-text");
}

jQuery(".mo_copy_certificate").click(function () {
  jQuery('#certificate-textarea').select();
  document.execCommand('copy');
});
