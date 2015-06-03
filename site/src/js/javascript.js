$(document).ready(function() {
    $.fn.datepicker.dates['nl'] = {
        days: ["Zondag", "Maandag", "Dinsdag", "Woensdag", "Donderdag", "Vrijdag", "Zaterdag"],
        daysShort: ["Zon", "Maa", "Din", "Woe", "Don", "Vri", "Zat"],
        daysMin: ["Zo", "Ma", "Di", "Wo", "Do", "Vr", "Za"],
        months: ["Januari", "Februari", "Maart", "April", "Mei", "Juni", "Juli", "Augustus", "September", "Oktober", "November", "December"],
        monthsShort: ["Jan", "Feb", "Maa", "Apr", "Mei", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dec"],
        today: "Vandaag",
        clear: "Reset"
    };
    $(".selector-for-chosen").chosen();
});

function passwordMatch()
{
    var pass1 = document.getElementById('password1');
    var pass2 = document.getElementById('password2');

    var matchColor = "#ffffff";
    var noMatchColor = "#FD9286";

    if(pass1.value == pass2.value){
        password2.style.backgroundColor = matchColor;
    }else{
        password2.style.backgroundColor = noMatchColor;
    }
}

function searchKeyPress(e)
{
    // look for window.event in case event isn't passed in
    e = e || window.event;
    if (e.keyCode == 13)
    {
        document.getElementById('btnSearch').click();
    }
}