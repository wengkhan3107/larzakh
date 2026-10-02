$(document).ready(function() {
    function updatePrice() {
        let servicePrice = 0;
        let userPrice = 0;

        const servicePricing = {
            "1": 0, "2": 100, "3": 200, "4": 300, "5": 400,
            "6": 460, "7": 528, "8": 560, "9": 616, "10": 675,
            "15": 980, "20": 1235, "30": 1798, "40": 2145, "50": 2156
        };

        const userPricing = {
            "1-100": 450, "101-200": 550, "201-300": 650, "301-400": 750,
            "401-500": 850, "501-1000": 1250, "1001-2000": 1600,
            "2001-3000": 1900, "3001-4000": 2150, "4001-5000": 2400,
            "5000+": "contact us"
        };

        const selectedSP = $("#mo_pricing_dropdown").val();
        servicePrice = servicePricing[selectedSP] || 0;

        const selectedUsers = $("#users_dropdown").val();
        userPrice = userPricing[selectedUsers] || 0;

        let totalPrice = (userPrice === "contact us")
            ? '<a href="mailto:{{ contact_email }}" style="color: #ff6600; font-weight: bold; text-decoration: none; font-size: 17px;">For More Contact Us</a>'
            : "$" + (servicePrice + userPrice);

        $("#price-plan").html(totalPrice);
    }

    $("#mo_pricing_dropdown, #users_dropdown").on("change", updatePrice);

    updatePrice(); // Initial price update
});