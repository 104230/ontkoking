const error = document.getElementById("error");
const params = new URLSearchParams(window.location.search);

if (error) {
    const errorType = params.get("error");

    if (errorType === "leeg") {
        error.textContent = "Vul je e-mail en wachtwoord in.";
    } else if (errorType === "ongeldig") {
        error.textContent = "Je e-mailadres of wachtwoord is onjuist.";
    } else {
        error.textContent = "";
    }
}