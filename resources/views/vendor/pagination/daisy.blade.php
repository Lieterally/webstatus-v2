{{-- Link pagination (ITK Pagination). The file name is kept so existing ->links('vendor.pagination.daisy') calls still work. --}}
@if ($paginator->hasPages())
    <x-itk.pagination :paginator="$paginator" :elements="$elements" />
@endif
