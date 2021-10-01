function addIscDhcpReservation() {
    ajaxRequestPost(
        "views/views.d/isc-dhcp-reservations.php",
        urlencodeObject({
            "add_hostname": txtHostname.value,
            "add_ip": txtIpAddress.value,
            "add_mac": txtMacAddress.value}),
        null,
        function() {
            refreshContent();
            alert("Reservation added successfully");
        }
    );
}
function removeIscDhcpReservation(hostname) {
    if(confirm(hostname+" wirklich entfernen?")) {
        ajaxRequestPost(
            "views/views.d/isc-dhcp-reservations.php",
            urlencodeObject({"remove_hostname": hostname}),
            null, refreshContent
        );
    }
}
