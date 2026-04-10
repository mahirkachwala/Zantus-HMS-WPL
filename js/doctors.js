if(localStorage.getItem("theme") === "dark"){
	document.body.classList.add("dark-mode");
}

// Keyup event filters doctor cards using text matching in the DOM.
document.getElementById("searchInput")
.addEventListener("keyup", function(){

let input = this.value.toLowerCase();
let cards = document.querySelectorAll(".doctor-card");

cards.forEach(function(card){
if(card.innerText.toLowerCase().includes(input)){
card.style.display = "inline-block";
}else{
card.style.display = "none";
}
});
});
