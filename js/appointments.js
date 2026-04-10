let appointments = [];

if(localStorage.getItem("theme") === "dark"){
	document.body.classList.add("dark-mode");
}

function updateCount(){
document.getElementById("appointmentCount").innerText =
"Total Appointments: " + appointments.length;
}

updateCount();

function addAppointment(name){
	appointments.push(name);
	updateCount();
}

// Click event updates in-memory array and DOM count text.
document.getElementById("addAppointmentBtn")
.addEventListener("click", function(){
let input = document.getElementById("patientNameInput");
let name = input && input.value ? input.value.trim() : "New Patient";
addAppointment(name);
alert("Appointment Added Successfully");
});
