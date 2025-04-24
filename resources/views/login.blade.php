<!DOCTYPE html>
<html>

<head>
    <title>HTML Login Form</title>
    <link rel="stylesheet" href="style.css">
</head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<link href='https://fonts.googleapis.com/css?family=Noto Sans Thai' rel='stylesheet'>
<style>
    /*style.css*/
    body {
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: sans-serif;
        line-height: 1.5;
        min-height: 100vh;
        background: #f3f3f3;
        flex-direction: column;
        margin: 0;
        font-family: 'Noto Sans Thai';
        font-size: 22px;
    }

    .main {
        background-color: #fff;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        padding: 10px 20px;
        transition: transform 0.2s;
        width: 500px;
        text-align: center;
    }

    h3 {
        color: #4CAF50;
    }

    label {
        display: block;
        width: 100%;
        margin-top: 10px;
        margin-bottom: 5px;
        text-align: left;
        color: #555;
        font-weight: bold;
        font-size: 20px;
    }

    input {
        display: block;
        width: 100%;
        margin-bottom: 15px;
        padding: 10px;
        box-sizing: border-box;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }

    button {
        padding: 15px;
        border-radius: 10px;
        margin-top: 15px;
        margin-bottom: 15px;
        border: none;
        color: white;
        cursor: pointer;
        background-color: #4CAF50;
        width: 100%;
        font-size: 16px;
    }

    .wrap {
        display: flex;
        justify-content: center;
        align-items: center;
    }
</style>

<body>
    <div class="main">
        <h3>ระบบนำเข้าหนังสือ</h3>
        <form action="{{url('/login/auth')}}" method="post">
            @csrf
            <label for="email">
                Username:
            </label>
            <input type="text" id="email" name="email" placeholder="Enter your Username" required autocomplete="off">
            <label for="password">
                Password:
            </label>
            <input type="password" id="password" name="password" placeholder="Enter your Password" required autocomplete="off">
            @error('email')
            <div class="alert alert-danger">{{$errors->first('email')}}</div>
            @enderror
            <div class="wrap">
                <button type="submit">
                    Login
                </button>
            </div>
        </form>
    </div>
</body>

</html>