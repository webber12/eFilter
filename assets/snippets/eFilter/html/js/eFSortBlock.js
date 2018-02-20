$(document).ready(function(){
    $("#eFilter_sort_block").on("click", "a.sort_vid", function(e){
        e.preventDefault();
        var sortBy = $(this).data("sortBy");
        var sortOrder = $(this).data("sortOrder");
        $("#eFilter_sort_block a.sort_vid").removeClass("active");
        $(this).addClass("active");
        $("input[name='sortBy']").val(sortBy);
        $("input[name='sortOrder']").val(sortOrder);
        $("#changesortBy").submit();
    })
    $("#eFilter_sort_block").on("change", "select[name='sortDisplay']", function(e){
        e.preventDefault();
        $("#changesortBy").submit();
    })
})