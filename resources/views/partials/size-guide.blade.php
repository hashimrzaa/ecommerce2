@php
    $rows = $kids
        ? [['0–2 Years', '70–92', '46–52', '45–50'], ['3–5 Years', '98–110', '54–58', '52–55'], ['6–8 Years', '116–128', '60–66', '56–59'], ['9–12 Years', '134–152', '68–78', '60–66'], ['13–16 Years', '158–170', '80–88', '67–74']]
        : ($men
            ? [['S', '36–38', '30–32', '27'], ['M', '38–40', '32–34', '28'], ['L', '40–42', '34–36', '29'], ['XL', '42–44', '36–38', '30'], ['XXL', '44–46', '38–40', '31'], ['XXXL', '46–48', '40–42', '32']]
            : [['XS', '32', '26', '36'], ['S', '34', '28', '37'], ['M', '36', '30', '38'], ['L', '38', '32', '39'], ['XL', '40', '34', '40'], ['XXL', '42', '36', '41']]);
    $heads = $kids ? ['Age', 'Height (cm)', 'Chest (cm)', 'Waist (cm)'] : ['Size', 'Chest (in)', 'Waist (in)', 'Length (in)'];
@endphp
<p class="text-sm text-muted">Measurements are body measurements and may vary by 1–2 {{ $kids ? 'cm' : 'inches' }} between styles. Between two sizes? Choose the larger one.</p>
<div class="mt-4 overflow-x-auto rounded-control border border-line">
    <table class="tbl">
        <thead><tr>@foreach ($heads as $head)<th scope="col">{{ $head }}</th>@endforeach</tr></thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>@foreach ($row as $i => $cell)<td class="{{ $i === 0 ? 'font-semibold' : 'tabular-nums' }}">{{ $cell }}</td>@endforeach</tr>
            @endforeach
        </tbody>
    </table>
</div>
<p class="mt-4 text-sm text-muted">Need help choosing? <a href="{{ wa_link('Hi, I need help choosing a size.') }}" target="_blank" rel="noopener" class="link">Ask us on WhatsApp</a>.</p>
