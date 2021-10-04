function addIscDhcpReservation() {
    btnAddReservation.disabled = true;
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
        },
        function(status, statusText, responseText) {
            btnAddReservation.disabled = false;
            alert(L__ERROR+' '+status+' '+statusText+"\n"+responseText);
        }
    );
}
function removeIscDhcpReservation(hostname, ip, mac) {
    if(confirm("Really delete "+hostname+"?")) {
        ajaxRequestPost(
            "views/views.d/isc-dhcp-reservations.php",
            urlencodeObject({"remove_hostname": hostname}),
            null, function() {
                refreshContent(function() {
                    // fill the textboxes with the deleted entry values for quickly changing a reservation
                    txtHostname.value = hostname;
                    txtIpAddress.value = ip;
                    txtMacAddress.value = mac;
                });
            }
        );
    }
}
