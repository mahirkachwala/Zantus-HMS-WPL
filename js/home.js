document.addEventListener("DOMContentLoaded", function () {
	// Task 1: Welcome Message
	alert("Welcome to Zantus Life Science Hospital");
	document.getElementById("welcomeMsg").innerText = "Welcome to Zantus Life Science Hospital";
	// Task 2: Patient Data Types
	let patientName = "Rohan Deshmukh";
	let age = 34;
	let isAdmitted = true;
	if(document.getElementById("patientInfo")) {
		document.getElementById("patientInfo").innerHTML = "Patient: " + patientName + "<br>Age: " + age + "<br>Admitted: " + isAdmitted;
	}
	// Task 3: Calculate Treatment Cost
	let consultationFee = 500;
	let days = 3;
	let total = consultationFee * days;
	if(document.getElementById("totalCost")) {
		document.getElementById("totalCost").innerText = "Total Treatment Cost = " + total;
	}
	// Task 4: Apply Insurance Discount
	let finalAmount = total > 1000 ? total - (total * 0.10) : total;
	if(document.getElementById("finalBill")) {
		document.getElementById("finalBill").innerText = "Final Bill After Insurance = " + finalAmount;
	}
	// Task 5: Department Selection
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
	// Task 6: Store Doctor List
	let doctors = ["Dr. Amit Sharma", "Dr. Priya Nair", "Dr. Rajesh Kulkarni"];
	if(document.getElementById("doctorList")) {
		let doctorList = document.getElementById("doctorList");
		for (let i = 0; i < doctors.length; i++) {
			let li = document.createElement("li");
			li.innerText = doctors[i];
			doctorList.appendChild(li);
		}
	}
	// Task 9: Search Doctor Availability
	// Removed output as per user request
	// Task 13: Theme Toggle
	document.getElementById("themeBtn").addEventListener("click", function () {
		document.body.classList.toggle("dark-mode");
		localStorage.setItem("theme", document.body.classList.contains("dark-mode") ? "dark" : "light");
	});
	if (localStorage.getItem("theme") === "dark") {
		document.body.classList.add("dark-mode");
	}
});
