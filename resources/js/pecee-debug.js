function pecee_show_debug(id) {
    var el = document.getElementById(id);
    document.getElementById(id).style.display = (el.style.display == 'block') ? 'none' : 'block';
}