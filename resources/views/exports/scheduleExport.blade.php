<!DOCTYPE html>
<html>
<head>
    <title>Horário</title>
    <style>
        .table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            font-family: 'Open Sans', sans-serif;
        }

        .table thead th {
            background-color: #f9fafb;
            color: #363636;
            font-weight: 700;
            padding: 0.75em 1em;
            text-align: left;
            border-bottom: 2px solid #ebeef2;
        }

        .table th {
            font-weight: 600;
        }

        .table td {
            text-align: center;
            vertical-align: top;
            border: 1px solid #ebeef2;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table.is-striped tbody tr:nth-child(even) {
            background-color: #f5f7fa;
        }
        .table.is-bordered {
            border: 1px solid #ebeef2;
        }
    </style>
</head>
<body>
    <table class="table is-striped is-bordered">
        <thead>
            <tr>
                <td></td>
                @foreach($data as $date)
                    <td>
                        @if($loop->index == 0 || $data[$loop->index - 1]['month'] !== $date['month'])
                            {{ $date['month'] }}
                        @endif
                            <br>

                        {{ $date['day'] }}
                        <br>
                        {{ $date['day_of_the_week'] }}
                    </td>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($users as $userIndex => $user)
            <tr>
                <td>{{ $user->name }}</td>
                @foreach($data as $dateIndex => $date)
                    <td>
                            @php
                                $shift = '-';
                                foreach ($date['nurses'] as $userShift){
                                    if($userShift['user'] == $user->id){
                                        $shift = $userShift['shift'];
                                        break;
                                    }
                                }
                            @endphp

                          {{ $shift }}
                   </td>
              @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
