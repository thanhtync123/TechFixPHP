// Chuẩn hoá tiếng Việt: bỏ dấu + lowercase
function removeDiacritics(str) {
  if (!str) return '';
  return String(str).normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/đ/g, 'd').replace(/Đ/g, 'D');
}
function vnNorm(s) { return removeDiacritics(s).toLowerCase(); }

// Áp dụng cho toàn bộ DataTables: chuẩn hoá dữ liệu cột khi search
$.fn.dataTable.ext.type.search.html = function(data) {
  return vnNorm(String(data).replace(/<[^>]*>/g, ' '));
};
$.fn.dataTable.ext.type.search.string = function(data) {
  return vnNorm(String(data));
};

// Đặt mặc định chung cho tất cả DataTables
$.extend(true, $.fn.dataTable.defaults, {
  order: [],                // giữ nguyên thứ tự DOM (ví dụ DESC từ SQL)
  pageLength: 50,
  lengthMenu: [5, 10, 20, 50],
  language: {
    search: "🔍 Tìm kiếm:",
    lengthMenu: "Hiển thị _MENU_ dòng",
    info: "Trang _PAGE_ / _PAGES_",
    paginate: { previous: "← Trước", next: "Sau →" },
    zeroRecords: "Không tìm thấy kết quả"
  },
  search: { smart: true, regex: false }
});

// Chuẩn hoá từ khoá người dùng cho mọi bảng có class .dt-vn
$(document).on('init.dt', function(e, settings) {
  var api = new $.fn.dataTable.Api(settings);
  var tableEl = api.table().node();
  if (!$(tableEl).hasClass('dt-vn')) return; // chỉ áp dụng cho bảng gắn class này

  var $input = $(api.table().container()).find('input[type=search]');
  $input.off('.vn').on('input.vn', function() {
    api.search(vnNorm(this.value)).draw();
  });
});