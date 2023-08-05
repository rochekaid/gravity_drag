jQuery(document).ready(function ($) {
  function total() {
    $(".sortx table")
      .find("tfoot")
      .html(
        "<b>TOTAL UNASSIGNED</b>: " +
          $(".sortx table").find(".ui-sortable-handle:visible").length
      );
  }
  $(".sortx tbody").addClass("t_sortable_fixed");
  $(".sortx table").addClass("t_draggable");
  $(".sortx table").find("[data-class='expand']").closest("tr").remove();
  jQuery.each($(".groups"), function () {
    var assigned = $(this).find("tbody tr:not(:first-child)").length;
    $(this).find(".assigned").html(assigned);
    var limit = $(this).find("thead ul .group_limit").text();
    $(this)
      .find(".available")
      .html(limit - assigned);
    if (limit - assigned < 0) {
      $(this).find(".available").addClass(".highlight");
    } else {
      $(this).find(".available").removeClass(".highlight");
    }
  });

  $("#drag_search").keyup(function () {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("drag_search");
    filter = input.value.toUpperCase();
    table = document.getElementById("t_draggable");
    tr = table.getElementsByTagName("tr");
    for (i = 0; i < tr.length; i++) {
      td = tr[i].getElementsByTagName("td")[0];
      if (td) {
        txtValue = td.textContent || td.innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
          tr[i].style.display = "";
        } else {
          tr[i].style.display = "none";
        }
      }
    }
    total();
  });
  $(".container").sortable({
    connectWith: ".table_container"
  });

  $("#sortable1").sortable({
  connectWith: "#sortable2",
  helper: function(event, el) {
    copyHelper = el.clone().insertAfter(el);
    return el.clone();
  },
  stop: function() {
    copyHelper && copyHelper.remove();
  }
});
$("#sortable2").sortable({
  receive: function(event, ui) {
    copyHelper = null;
  }
});

function removeDuplicateCells(tableId) {
        var seen = {};
        $('#' + tableId + ' td').each(function() {
            var cellText = $(this).text().trim();
            if (seen[cellText]) {
                $(this).remove();
            } else {
                seen[cellText] = true;
            }
        });
         total();
    }

  if($('table').hasClass("select")){
  $("tbody.t_sortable,tbody.t_sortable_fixed")
    .sortable({
      connectWith: ".t_sortable",
      items: "tr:not(:first-child)",
      dropOnEmpty: true,
      appendTo: "body",
      zIndex: 10000,
      over: function () {
        $(this).parent().addClass("valid");
      },
      out: function () {
        $(this).parent().removeClass("valid");
      },
      receive: function (event, ui) {
        var limit = $(this).parent().find("tr th ul .group_limit").text();
        if ($(this).children().length > parseInt(limit) + 1) {
          $(ui.sender).sortable("cancel");
          alert("This group has reached its limit");
        }
        if (!ui.item.find("td button").length > 0) {
          ui.item
            .find("td")
            .append(
              '<button style="background:none;padding:0 20px;color:red" class="revert"><span class="dashicons dashicons-no"></span></button>'
            );
        }
        var group = $(event.target).data('option');
        var drag_item = ui.item.find("td:first-child a").attr("id");
        var type = ui.item.find("td:first-child a").attr("data-type");
        document.getElementById("draggable_group_name").value = group;
        document.getElementById("draggable_entry_id").value = drag_item;
        document.getElementById("draggable_type_name").value = type;
        jQuery("#draggable_entry_id").trigger("change");
        $(".t_draggable .revert").remove();
        var assigned = ui.item.parent().find("tr:not(:first-child)").length;
        ui.item.parent().parent().find(".assigned").html(assigned);
        var limit = ui.item
          .parent()
          .parent()
          .find("thead ul .group_limit")
          .text();
        ui.item
          .parent()
          .parent()
          .find(".available")
          .html(limit - assigned);
        var tab_parent = ui.item
          .parent()
          .parent()
          .attr("id");
        removeDuplicateCells(tab_parent);
      }
    })
    .disableSelection();
  }else{
    $("tbody.t_sortable,tbody.t_sortable_fixed")
    .sortable({
      connectWith: ".t_sortable",
      items: "tr:not(:first-child)",
      helper: function(event, el) {
        //if($(this).data('type') == 'multiselect'){
          copyHelper = el.clone().insertAfter(el);
          return el.clone();
        //}
      },
      dropOnEmpty: true,
      appendTo: "body",
      zIndex: 10000,
      over: function () {
        $(this).parent().addClass("valid");
      },
      out: function () {
        $(this).parent().removeClass("valid");
      },
      receive: function (event, ui) {
        var limit = $(this).parent().find("tr th ul .group_limit").text();
        if ($(this).children().length > parseInt(limit) + 1) {
          $(ui.sender).sortable("cancel");
          alert("This group has reached its limit");
        }
        if (!ui.item.find("td button").length > 0) {
          ui.item
            .find("td")
            .append(
              '<button style="background:none;padding:0 20px;color:red" class="revert"><span class="dashicons dashicons-no"></span></button>'
            );
        }
        var group = $(event.target).data('option');
        var drag_item = ui.item.find("td:first-child a").attr("id");
        var type = ui.item.find("td:first-child a").attr("data-type");
        document.getElementById("draggable_group_name").value = group;
        document.getElementById("draggable_entry_id").value = drag_item;
        document.getElementById("draggable_type_name").value = type;
        jQuery("#draggable_entry_id").trigger("change");
        $(".t_draggable .revert").remove();
        var assigned = ui.item.parent().find("tr:not(:first-child)").length;
        ui.item.parent().parent().find(".assigned").html(assigned);
        var limit = ui.item
          .parent()
          .parent()
          .find("thead ul .group_limit")
          .text();
        ui.item
          .parent()
          .parent()
          .find(".available")
          .html(limit - assigned);
        
        var tab_parent = ui.item
          .parent()
          .parent()
          .attr("id");
        removeDuplicateCells(tab_parent);
      }
    })
    .disableSelection();
  }
  function sort() {
    var sortableList = $(".sortx table");
    var listitems = $("tr:not(:first-child)", sortableList);
    listitems.sort(function (a, b) {
      return $(a).text().toUpperCase() > $(b).text().toUpperCase() ? 1 : -1;
    });
    sortableList.append(listitems);
    total();
  }
  $("tbody").on("click", ".revert", function () {
    var drag_item = $(this).parent().find("a").attr("id");
    if($(this).parent().parent().parent().parent().hasClass("multiselect")){
      var group = "-" + $(this).parent().parent().parent().data("option");
    }else{
       var group = "";
    }
    document.getElementById("draggable_group_name").value = group;
    document.getElementById("draggable_entry_id").value = drag_item;
    $("#draggable_entry_id").trigger("change");
    var tr = $(this).parent().parent();
    $(this)
      .closest("thead")
      .find(".assigned")
      .html($(this).closest("tbody").find("tr").length);
    $(".sortx").find("tbody").append(tr.find("button").remove().end());
    sort();
    removeDuplicateCells('t_draggable');
   
  });

  $(".dragform").each(function () {
    document.getElementById("draggable_entry_id").onchange = function () {
      $.ajax({
        type: "POST",
        url: ajax_object.ajax_url,
        data: $(this.form).serialize(),
        dataType: "json",
        success: function (response) {
          if (response.status == "success") {
            $("#mess").fadeIn(0);
            $("#mess").html("Form successfully updated");
            $("#mess").delay(1000).fadeOut("slow");
          }
          $("#df-msg").html(response.errmessage);
        }
      });
    };
  });
  sort();
  //$('.sortx table').find('tfoot').html('<b>TOTAL UNASSIGNED</b>: ' + $('#count_unassigned').val() );
  //search lodging buckets
  $("#searchp").keyup(function () {
    var seterm = $("#searchp").val();
    for (var i = 0; i < $(".table_container").length; i++) {
      $(".table_container:eq(" + i + ")").css("display", "inline-block");
      if (
        $(".table_container:eq(" + i + ")")
          .text()
          .toLowerCase()
          .indexOf(seterm) < 0
      ) {
        $(".table_container:eq(" + i + ")").css("display", "none");
      }
    }
  });
});
