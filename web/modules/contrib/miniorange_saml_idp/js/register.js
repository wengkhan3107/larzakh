function click_to_upgrade_or_register(url){
  if(url.search("xecurify")==-1)
    window.location = url;
  else
    window.open(url,"_blank" );
}

