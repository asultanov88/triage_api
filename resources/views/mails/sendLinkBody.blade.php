<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Triage Translation</title>

 
</head>

<body>
<h1>Triage Portal User Registration</h1>
<br><br>
<table>
    <tbody>
        <tr>
            <td>
            <strong>
                Requestor:
            </strong>
            </td>
                {{$user -> first_name}} {{$user -> last_name}} 
            <td>
            </td>            
        </tr>
    </tbody>
</table>
<br><br>
<a href="http://localhost:3333/#/guest/staff-setup/{{$language}}?id={{$link}}">Click here to complete user registration</a>
 

</body>

</html>