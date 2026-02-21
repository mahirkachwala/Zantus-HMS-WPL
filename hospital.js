// Task 1: Welcome Message
document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("welcomeMsg").innerText =
        "Welcome to Zantus Life Science Hospital";
});



// Task 2: Patient Data Types
let patientName = "Rohan Deshmukh";
let age = 34;
let isAdmitted = true;

document.getElementById("patientInfo").innerHTML =
    "Patient: " + patientName +
    "<br>Age: " + age +
    "<br>Admitted: " + isAdmitted;



// Task 3: Calculate Treatment Cost
let consultationFee = 500;
let days = 3;
let total = consultationFee * days;
document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("totalCost").innerText =
        "Total Treatment Cost = ₹" + total;
});


// Task 4: Apply Insurance Discount
let finalAmount;

if (total > 1000) {
    finalAmount = total - (total * 0.10);
} else {
    finalAmount = total;
}

document.getElementById("finalBill").innerText =
    "Final Bill After Insurance = ₹" + finalAmount;


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

document.getElementById("departmentInfo").innerText = deptMessage;


// Task 6: Store Doctor List
let doctors = ["Dr. Amit Sharma", "Dr. Priya Nair", "Dr. Rajesh Kulkarni"];

let doctorList = document.getElementById("doctorList");

for (let i = 0; i < doctors.length; i++) {
    let li = document.createElement("li");
    li.innerText = doctors[i];
    doctorList.appendChild(li);
}


// Task 7: Add Appointment
let appointments = [];

function addAppointment(name) {
    appointments.push(name);
    displayAppointments();
}


// Task 8: Count Appointments
function displayAppointments() {
    document.getElementById("appointmentCount").innerText =
        "Total Appointments: " + appointments.length;
}


// Task 9: Search Doctor Availability
let searchDoctor = "Dr. Priya Nair";
let found = false;

for (let i = 0; i < doctors.length; i++) {
    if (doctors[i] === searchDoctor) {
        found = true;
        break;
    }
}

document.getElementById("doctorSearch").innerText =
    found ? "Doctor Available" : "Doctor Not Available";


// Task 10: Calculate Total Billing
let billingArray = [1500, 3000, 900];

function calculateTotalBill() {
    let sum = 0;
    for (let i = 0; i < billingArray.length; i++) {
        sum += billingArray[i];
    }
    document.getElementById("billingTotal").innerText =
        "Total Hospital Bill = ₹" + sum;
}


// Task 11: Add Appointment Button
document.getElementById("addBtn").addEventListener("click", function () {
    addAppointment("New Patient");
    alert("Appointment Added Successfully");
});


// Task 12: Search Doctors
document.getElementById("searchInput").addEventListener("keyup", function () {
    let input = this.value.toLowerCase();
    let items = document.querySelectorAll("#doctorList li");

    items.forEach(function (item) {
        if (item.innerText.toLowerCase().includes(input)) {
            item.style.display = "block";
        } else {
            item.style.display = "none";
        }
    });
});


// Task 13: Theme Toggle
document.getElementById("themeBtn").addEventListener("click", function () {
    document.body.classList.toggle("dark-mode");
});

// Patient Bill Calculator (Billing Tab)
function createPatientBill() {
    let name = document.getElementById("billPatientName").value;
    let days = parseInt(document.getElementById("billDays").value);
    let consultationFee = 500;
    let total = consultationFee * days;
    let finalAmount = total > 1000 ? total - (total * 0.10) : total;
    document.getElementById("patientBillResult").innerHTML =
        "Patient: " + name + "<br>Days: " + days + "<br>Total Cost: ₹" + total;
    document.getElementById("patientBillFinal").innerHTML =
        "Final Bill After Insurance: ₹" + finalAmount;
}

// Attach event listener for patient bill calculation
if (document.getElementById("billCalcBtn")) {
    document.getElementById("billCalcBtn").addEventListener("click", createPatientBill);
}
