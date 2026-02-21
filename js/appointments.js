// Task 7: Appointment Array
let appointments = [];

// Load theme on page load
if(localStorage.getItem("theme") === "dark"){
	document.body.classList.add("dark-mode");
}

// Task 8: Count Appointments
function updateCount(){
document.getElementById("appointmentCount").innerText =
"Total Appointments: " + appointments.length;
}

// Initialize count on load
updateCount();

// Task 7 helper: addAppointment function
function addAppointment(name){
	appointments.push(name);
	updateCount();
}

// Task 11: Click Event
document.getElementById("addAppointmentBtn")
.addEventListener("click", function(){
let input = document.getElementById("patientNameInput");
let name = input && input.value ? input.value.trim() : "New Patient";
addAppointment(name);
alert("Appointment Added Successfully");
});
