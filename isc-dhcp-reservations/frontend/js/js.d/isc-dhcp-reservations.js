function addIscDhcpReservation() {
    btnAddReservation.disabled = true;
    ajaxRequestPost(
        "views/views.d/isc-dhcp-reservations.php",
        urlencodeObject({
            "server": txtServer.value,
            "add_hostname": txtHostname.value,
            "add_ip": txtIpAddress.value,
            "add_mac": txtMacAddress.value
        }), null,
        function() {
            refreshContent();
            emitMessage("Reservation added successfully", txtHostname.value+"\n"+txtIpAddress.value+"\n"+txtMacAddress.value, MESSAGE_TYPE_SUCCESS);
        },
        function(status, statusText, responseText) {
            btnAddReservation.disabled = false;
            emitMessage(L__ERROR+' '+status+' '+statusText, responseText, MESSAGE_TYPE_ERROR);
        }
    );
}
function removeIscDhcpReservation(hostname, ip, mac) {
    if(confirm("Really delete "+hostname+"?")) {
        ajaxRequestPost(
            "views/views.d/isc-dhcp-reservations.php",
            urlencodeObject({
                "server": txtServer.value,
                "remove_hostname": hostname
            }), null,
            function() {
                refreshContent(function() {
                    // fill the textboxes with the deleted entry values for quickly changing a reservation
                    txtHostname.value = hostname;
                    txtIpAddress.value = ip;
                    txtMacAddress.value = mac;
                });
                emitMessage("Reservation removed successfully", hostname+"\n"+ip+"\n"+mac, MESSAGE_TYPE_SUCCESS);
            }
        );
    }
}
