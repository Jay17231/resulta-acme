jQuery(document).ready(function() {
   jQuery("#acme_nfl_team_data").DataTable({
      columnDefs : [
    { type : 'name', targets : [5] }
],
   });
});
