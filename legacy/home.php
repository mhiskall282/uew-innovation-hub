  <!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>UEW Innovation Hub | Project Management System</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f5f7fb;
            color: #263238;
        }


        /* =========================
           NAVIGATION BAR
        ========================== */

        .navbar {
    background: linear-gradient(90deg, #1f4e79, #5b3fa3);
    min-height: 78px;
    padding: 10px 6%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 3px 12px rgba(0,0,0,0.15);

    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
}

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand img {
            width: 62px;
            height: 62px;
            object-fit: contain;
            background-color: white;
            border-radius: 8px;
            padding: 4px;
        }

        .brand-text h1 {
            color: white;
            font-size: 21px;
            margin-bottom: 4px;
        }

        .brand-text p {
            color: #e4ddff;
            font-size: 13px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            transition: 0.2s;
        }

        .nav-links a:hover {
            background-color: rgba(255,255,255,0.15);
        }

        .nav-links .nav-add {
            background-color: white;
            color: #5b3fa3;
        }

        .nav-links .nav-add:hover {
            background-color: #f0ecff;
        }


        /* =========================
           HERO
        ========================== */

        .hero {
            position: relative;
            overflow: hidden;
                margin-top: 78px;
            background: linear-gradient(120deg, #1f4e79, #4d4395, #6a4bbc);
            color: white;
            text-align: center;
            padding: 95px 20px 125px;
        }

        .hero::before {
            content: "";
            position: absolute;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.06);
            top: -130px;
            left: -80px;
        }

        .hero::after {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.05);
            bottom: -250px;
            right: -100px;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 900px;
            margin: auto;
        }

        .hero-label {
            display: inline-block;
            border: 1px solid rgba(255,255,255,0.5);
            padding: 8px 17px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1.5px;
            margin-bottom: 20px;
            background-color: rgba(255,255,255,0.08);
        }

        .hero h2 {
            font-size: 46px;
            line-height: 1.15;
            margin-bottom: 20px;
        }

        .hero h2 span {
            color: #ddd4ff;
        }

        .hero p {
            max-width: 720px;
            margin: 0 auto 35px;
            color: #edf2ff;
            line-height: 1.8;
            font-size: 16px;
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 13px;
            flex-wrap: wrap;
        }

        .hero-button {
            display: inline-block;
            padding: 14px 26px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: 0.2s;
        }

        .primary-button {
            background-color: white;
            color: #1f4e79;
        }

        .primary-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .secondary-button {
            border: 1px solid rgba(255,255,255,0.7);
            color: white;
            background-color: rgba(255,255,255,0.06);
        }

        .secondary-button:hover {
            background-color: rgba(255,255,255,0.15);
            transform: translateY(-2px);
        }


        /* =========================
           SYSTEM INTRO
        ========================== */

        .intro-wrapper {
            max-width: 1120px;
            margin: -55px auto 0;
            padding: 0 20px;
            position: relative;
            z-index: 5;
        }

        .intro-box {
            background-color: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            border: 1px solid #e4e8ef;
        }

        .intro-heading {
            text-align: center;
            margin-bottom: 28px;
        }

        .intro-heading h3 {
            color: #1f4e79;
            font-size: 25px;
            margin-bottom: 8px;
        }

        .intro-heading p {
            color: #777;
            font-size: 14px;
        }


        /* =========================
           FEATURE CARDS
        ========================== */

        .features {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .feature-card {
            background-color: white;
            border: 1px solid #e2e7ef;
            border-radius: 9px;
            padding: 25px 18px;
            text-align: center;
            transition: 0.25s;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1f4e79, #6a4bbc);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 22px rgba(31,78,121,0.12);
        }

        .feature-icon {
            width: 58px;
            height: 58px;
            margin: 0 auto 15px;
            border-radius: 50%;
            background: linear-gradient(135deg, #eaf2f8, #eee9ff);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
        }

        .feature-card h4 {
            color: #1f4e79;
            font-size: 16px;
            margin-bottom: 9px;
        }

        .feature-card p {
            color: #666;
            font-size: 13px;
            line-height: 1.65;
        }


        /* =========================
           ABOUT SECTION
        ========================== */

        .about-section {
            max-width: 1120px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .about-box {
            background-color: white;
            border-radius: 12px;
            border: 1px solid #e2e7ef;
            padding: 38px;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 50px;
        }

        .about-content h3,
        .management-content h3 {
            color: #1f4e79;
            font-size: 23px;
            margin-bottom: 15px;
        }

        .about-content p {
            color: #666;
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 13px;
        }

        .management-list {
            list-style: none;
        }

        .management-list li {
            padding: 12px 0;
            border-bottom: 1px solid #edf0f4;
            font-size: 14px;
            color: #555;
        }

        .management-list li:last-child {
            border-bottom: none;
        }

        .management-list span {
            color: #6a4bbc;
            font-weight: bold;
            margin-right: 8px;
        }


         


        /* =========================
           FOOTER
        ========================== */

        .footer {
            background-color: #152f4a;
            color: white;
            text-align: center;
            padding: 16px 18px;
            margin-top: 50px;
        }

        .footer h4 {
            margin-bottom: 7px;
            font-size: 16px;
        }

        .footer p {
            color: #cbd8e5;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .footer small {
            color: #9fb2c4;
            font-size: 11px;
        }


        /* =========================
           RESPONSIVE DESIGN
        ========================== */

        @media (max-width: 900px) {

            .navbar {
                flex-direction: column;
                gap: 12px;
                padding: 15px 20px;
            }

            .features {
                grid-template-columns: repeat(2, 1fr);
            }

            .about-box {
                grid-template-columns: 1fr;
                gap: 25px;
            }

            .hero h2 {
                font-size: 38px;
            }

        }


        @media (max-width: 550px) {

            .brand {
                width: 100%;
                justify-content: center;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .hero {
                padding: 70px 18px 105px;
            }

            .hero h2 {
                font-size: 30px;
            }

            .hero p {
                font-size: 14px;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .intro-box,
            .about-box {
                padding: 22px;
            }

            .cta-box {
                padding: 28px 20px;
            }

        }

    </style>

</head>


<body>


    <!-- =========================
         NAVIGATION
    ========================== -->

    <nav class="navbar">

        <div class="brand">

            <img
                src="innovation_hub_logo.jpg"
                alt="UEW Innovation Hub Logo"
            >

            <div class="brand-text">

                <h1>UEW Innovation Hub</h1>

                <p>Project Management System</p>

            </div>

        </div>


        <div class="nav-links">

            <a href="home.php">Home</a>

            <a href="index.php">Projects</a>

            <a href="add_project.php" class="nav-add">
                Add Project
            </a>

        </div>

    </nav>



    <!-- =========================
         HERO
    ========================== -->

    <section class="hero">

        <div class="hero-content">

            <div class="hero-label">
                UNIVERSITY OF EDUCATION, WINNEBA
            </div>

            <h2>
                Innovation Hub
                <span>Project Management System</span>
            </h2>

            <p>
                A centralized platform for recording, organizing,
                managing and reporting Innovation Hub projects and
                the organisations involved in them.
            </p>


            <div class="hero-buttons">

                <a
                    href="index.php"
                    class="hero-button primary-button"
                >
                    View Project Records
                </a>

                <a
                    href="add_project.php"
                    class="hero-button secondary-button"
                >
                    Add New Project
                </a>

            </div>

        </div>

    </section>



    <!-- =========================
         SYSTEM FUNCTIONS
    ========================== -->

    <section class="intro-wrapper">

        <div class="intro-box">

            <div class="intro-heading">

                <h3>System Functions</h3>

                <p>
                    Key functions available within the Innovation Hub
                    Project Management System.
                </p>

            </div>


            <div class="features">


                <div class="feature-card">

                    <div class="feature-icon">
                        📁
                    </div>

                    <h4>Project Management</h4>

                    <p>
                        Create, view, search and manage
                        Innovation Hub project records.
                    </p>

                </div>


                <div class="feature-card">

                    <div class="feature-icon">
                        🏢
                    </div>

                    <h4>Organisations</h4>

                    <p>
                        Maintain records of organisations
                        associated with Innovation Hub projects.
                    </p>

                </div>


                <div class="feature-card">

                    <div class="feature-icon">
                        🤝
                    </div>

                    <h4>Sponsors & Partners</h4>

                    <p>
                        Record organisations and identify
                        their relationship with each project.
                    </p>

                </div>


                <div class="feature-card">

                    <div class="feature-icon">
                        🖨️
                    </div>

                    <h4>Project Reports</h4>

                    <p>
                        Prepare project information for
                        viewing and printing.
                    </p>

                </div>


            </div>

        </div>

    </section>



    <!-- =========================
         ABOUT SYSTEM
    ========================== -->

    <section class="about-section">

        <div class="about-box">


            <div class="about-content">

                <h3>About the System</h3>

                <p>
                    The UEW Innovation Hub Project Management System
                    provides a structured platform for managing
                    information about projects carried out through
                    the Innovation Hub.
                </p>

                <p>
                    The system brings project information and
                    participating organisations together in an
                    organised digital environment.
                </p>

            </div>


            <div class="management-content">

                <h3>Information Managed</h3>

                <ul class="management-list">

                    <li>
                        <span>✓</span>
                        Project identification and title
                    </li>

                    <li>
                        <span>✓</span>
                        Project start and end dates
                    </li>

                    <li>
                        <span>✓</span>
                        Number of beneficiaries
                    </li>

                    <li>
                        <span>✓</span>
                        Sponsors and partners
                    </li>

                    <li>
                        <span>✓</span>
                        Project records and reports
                    </li>

                </ul>

            </div>


        </div>

    </section>



    <!-- =========================
         CALL TO ACTION
    ========================== -->
 



    <!-- =========================
         FOOTER
    ========================== -->

    <footer class="footer">

        <h4>UEW Innovation Hub</h4>

        <p>
            University of Education, Winneba
        </p>

        <small>
            Project Management System
        </small>

    </footer>


</body>
</html>

