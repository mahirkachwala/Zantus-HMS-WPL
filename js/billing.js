// Load theme on page load
if(localStorage.getItem("theme") === "dark"){
	document.body.classList.add("dark-mode");
}

// Task 10: Calculate Total Billing
let billingArray = [1500, 3000, 900];
function calculateTotalBill(){
let sum = 0;
for(let i=0; i<billingArray.length; i++){
sum += billingArray[i];
}
document.getElementById("billingTotal").innerText =
"Total Hospital Bill = " + sum;
}

// Task 4: Patient Bill Calculator
function createPatientBill() {
    var name = document.getElementById("billPatientName").value;
    var days = parseInt(document.getElementById("billDays").value);
    var consultationFee = 500;
    var total = consultationFee * days;
    var finalAmount = total > 1000 ? total - (total * 0.10) : total;
    document.getElementById("patientBillResult").innerHTML =
        "Patient: " + name + "<br>Days: " + days + "<br>Total Cost: ₹" + total;
    document.getElementById("patientBillFinal").innerHTML =
        "Final Bill After Insurance: ₹" + finalAmount;
}
// Attach event listener for patient bill calculation
if (document.getElementById("billCalcBtn")) {
    document.getElementById("billCalcBtn").addEventListener("click", createPatientBill);
}
