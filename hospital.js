document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("welcomeMsg").innerText =
        "Welcome to Zantus Life Science Hospital";
});
let patientName = "Rohan Deshmukh";
let age = 34;
let isAdmitted = true;

document.getElementById("patientInfo").innerHTML =
    "Patient: " + patientName +
    "<br>Age: " + age +
    "<br>Admitted: " + isAdmitted;
let consultationFee = 500;
let days = 3;
let total = consultationFee * days;
document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("totalCost").innerText =
        "Total Treatment Cost = ₹" + total;
});
let finalAmount;

if (total > 1000) {
    finalAmount = total - (total * 0.10);
} else {
    finalAmount = total;
}

document.getElementById("finalBill").innerText =
    "Final Bill After Insurance = ₹" + finalAmount;
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
let doctors = ["Dr. Amit Sharma", "Dr. Priya Nair", "Dr. Rajesh Kulkarni"];

let doctorList = document.getElementById("doctorList");

for (let i = 0; i < doctors.length; i++) {
    let li = document.createElement("li");
    li.innerText = doctors[i];
    doctorList.appendChild(li);
}
let appointments = [];

function addAppointment(name) {
    appointments.push(name);
    displayAppointments();
}
function displayAppointments() {
    document.getElementById("appointmentCount").innerText =
        "Total Appointments: " + appointments.length;
}
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
let billingArray = [1500, 3000, 900];

function calculateTotalBill() {
    let sum = 0;
    for (let i = 0; i < billingArray.length; i++) {
        sum += billingArray[i];
    }
    document.getElementById("billingTotal").innerText =
        "Total Hospital Bill = ₹" + sum;
}
document.getElementById("addBtn").addEventListener("click", function () {
    addAppointment("New Patient");
    alert("Appointment Added Successfully");
});
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
document.getElementById("themeBtn").addEventListener("click", function () {
    document.body.classList.toggle("dark-mode");
});
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
if (document.getElementById("billCalcBtn")) {
    document.getElementById("billCalcBtn").addEventListener("click", createPatientBill);
}
