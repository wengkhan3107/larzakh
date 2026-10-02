function Instance_Pricing(x){
 document.getElementById("instances_premium").value = x;
 const price_premium={1:"450",2:"550",3:"650",4:"750",5:"850",6:"1250",7:"1600",8:"1900",9:"2150",10:"2400",11:""};

 if(x < 11){
 document.getElementById("premium_price").innerHTML="$ "+ price_premium[x]+"/year";
 }
 else{
  document.getElementById("premium_price").innerHTML="<div style=\"font-size:0.6em;\"> <a href=\"https://www.miniorange.com/contact\"><b>Request a Quote</b></a></div>";
 }
 
}