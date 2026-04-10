document.addEventListener("DOMContentLoaded", function () {
	// DOMContentLoaded ensures elements exist before DOM updates.
	alert("Welcome to Zantus Life Science Hospital");
	document.getElementById("welcomeMsg").innerText = "Welcome to Zantus Life Science Hospital";
	let patientName = "Rohan Deshmukh";
	// JavaScript data types here include string, number, and boolean.
	let age = 34;
	let isAdmitted = true;
	if(document.getElementById("patientInfo")) {
		document.getElementById("patientInfo").innerHTML = "Patient: " + patientName + "<br>Age: " + age + "<br>Admitted: " + isAdmitted;
	}
	let consultationFee = 500;
	let days = 3;
	let total = consultationFee * days;
	if(document.getElementById("totalCost")) {
		document.getElementById("totalCost").innerText = "Total Treatment Cost = " + total;
	}
	let finalAmount = total > 1000 ? total - (total * 0.10) : total;
	if(document.getElementById("finalBill")) {
		document.getElementById("finalBill").innerText = "Final Bill After Insurance = " + finalAmount;
	}
	let department = "cardiology";
	let deptMessage;
	switch (department) {
		case "cardiology":
			deptMessage = "Cardiology Department";
			break;
		case "neurology":
			deptMessage = "Neurology Department";
			break;
		case "orthopedics":
			deptMessage = "Orthopedics Department";
			break;
		default:
			deptMessage = "General Department";
	}
	if(document.getElementById("departmentInfo")) {
		document.getElementById("departmentInfo").innerText = deptMessage;
	}
	let doctors = ["Dr. Amit Sharma", "Dr. Priya Nair", "Dr. Rajesh Kulkarni"];
	// Arrays and loops are used to repeat DOM list creation.
	if(document.getElementById("doctorList")) {
		let doctorList = document.getElementById("doctorList");
		for (let i = 0; i < doctors.length; i++) {
			let li = document.createElement("li");
			li.innerText = doctors[i];
			doctorList.appendChild(li);
		}
	}
	// Click event toggles theme and persists preference in localStorage.
	document.getElementById("themeBtn").addEventListener("click", function () {
		document.body.classList.toggle("dark-mode");
		localStorage.setItem("theme", document.body.classList.contains("dark-mode") ? "dark" : "light");
	});
	if (localStorage.getItem("theme") === "dark") {
		document.body.classList.add("dark-mode");
	}
});
