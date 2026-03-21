<style>
	.zantus-brand {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 10px;
		font-weight: 600;
		width: 100%;
		text-align: center;
	}
	.zantus-brand img {
		width: 30px;
		height: 30px;
		border-radius: 6px;
		background: #fff;
		padding: 2px;
	}
	.zantus-subtitle {
		font-size: 12px;
		line-height: 1.3;
		color: #bcd0ee;
		margin: 10px 0 8px;
		text-align: center;
	}
	.profile.clearfix {
		display: flex;
		align-items: center;
		gap: 10px;
		padding: 0 10px;
		flex-wrap: wrap;
	}
	.profile_info h2 {
		font-size: 16px;
		margin: 2px 0;
	}
	.zantus-meta {
		font-size: 12px;
		color: #cfe0ff;
		margin: 0;
	}
	.left_col, .nav_title {
		background: #1e3a8a !important;
	}
	.nav_menu {
		background: #0f172a !important;
	}
	.site_title {
		color: #fff !important;
	}
	body {
		font-family: "Segoe UI", Tahoma, Arial, sans-serif;
		letter-spacing: .1px;
		font-size: 14px;
		line-height: 1.6;
	}
	.right_col {
		background: #f4f7fb;
	}
	.x_content, .x_content p, .x_content td, .x_content th {
		font-size: 14px;
	}
	label, .control-label, .form-group label {
		font-size: 14px;
		font-weight: 600;
		color: #334155;
	}
	.form-control, select.form-control, textarea.form-control {
		height: auto;
		min-height: 40px;
		font-size: 14px;
		padding: 9px 12px;
		border-radius: 8px;
		border-color: #d7ddea;
	}
	.x_panel {
		border: 1px solid #e6ebf5;
		border-radius: 12px;
		box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
	}
	.x_title h2 {
		color: #1e3a8a;
		font-weight: 700;
	}
	.side-menu>li>a,
	.nav.child_menu li a {
		font-size: 14px;
	}
	.table thead th,
	table th {
		background: #1e3a8a;
		color: #fff;
		font-size: 13px;
		font-weight: 600;
		letter-spacing: .2px;
	}
	.table td, table td {
		font-size: 14px;
		vertical-align: middle !important;
	}
	.btn-primary {
		background: #1e3a8a;
		border-color: #1e3a8a;
	}
	.btn-primary:hover {
		background: #1e40af;
		border-color: #1e40af;
	}
	.btn-transparent.btn-xs,
	.btn-transparent.btn-xs.tooltips {
		background: #dc2626 !important;
		border: 1px solid #dc2626 !important;
		color: #fff !important;
		border-radius: 6px;
	}
	.btn-transparent.btn-xs:hover,
	.btn-transparent.btn-xs.tooltips:hover {
		background: #b91c1c !important;
		border-color: #b91c1c !important;
	}
	.btn-cancel {
		background: #dc2626 !important;
		border-color: #dc2626 !important;
		color: #fff !important;
	}
	.btn-cancel:hover {
		background: #b91c1c !important;
		border-color: #b91c1c !important;
	}
	.status-active {
		color: #16a34a;
		font-weight: 700;
	}
	.status-cancelled {
		color: #dc2626;
		font-weight: 700;
	}
</style>
<div class="container body">
	<div class="main_container">
		<!-- page content -->
		<div class="col-md-3 left_col">
			<div class="left_col scroll-view">
				<div class="navbar nav_title" style="border: 0;">
					<a href="dashboard.php" class="site_title zantus-brand"><img src="assets/images/zantus-logo.jpg" alt="Zantus Logo"> <span>Zantus HMS</span></a>
				</div>
				<div class="clearfix"></div>
				<!-- menu profile quick info -->
				<div class="profile clearfix">
					<div class="zantus-subtitle">Zantus Life Science Hospital</div>
					<div class="profile_pic">
						<img src="assets/images/zantus-logo.jpg" alt="..." class="img-circle profile_img">
					</div>
					<div class="profile_info">
						<span>Welcome,</span>
						<h2><?php echo htmlentities($_SESSION['fullName'] ?? 'User'); ?></h2>
						<p class="zantus-meta">Role: Patient | ID: <?php echo (int)($_SESSION['id'] ?? 0); ?></p>
					</div>
				</div>
				<!-- /menu profile quick info -->
				<br />
				<?php include('include/sidebar.php');?>
				<!-- /sidebar menu -->
				<!-- /menu footer buttons -->
				<div class="sidebar-footer hidden-small">
					<a data-toggle="tooltip" data-placement="top" title="Logout" href="logout.php" style="width:100%;text-align:center;">
						<span class="glyphicon glyphicon-off" aria-hidden="true"></span>
					</a>
				</div>
				<!-- /menu footer buttons -->
			</div>
		</div>
		<!-- top navigation -->
		<div class="top_nav">
			<div class="nav_menu">
				<div class="nav toggle">
					<a id="menu_toggle"><i class="fa fa-bars"></i></a>
				</div>

				<nav class="nav navbar-nav">
					<ul class=" navbar-right">
						<li class="nav-item dropdown open" style="padding-left: 15px;">
							<a href="javascript:;" class="user-profile dropdown-toggle" aria-haspopup="true" id="navbarDropdown" data-toggle="dropdown" aria-expanded="false">
								<img src="assets/images/zantus-logo.jpg" alt=""><?php echo htmlentities($_SESSION['fullName'] ?? 'User'); ?>
							</a>
							<div class="dropdown-menu dropdown-usermenu pull-right" aria-labelledby="navbarDropdown">
								<a class="dropdown-item"  href="edit-profile.php"> My Profile</a>
								<a class="dropdown-item"  href="change-password.php"> Change Password</a>
								<a class="dropdown-item"  href="logout.php"><i class="fa fa-sign-out pull-right"></i> Log Out</a>
							</div>
						</li>
					</ul>
				</nav>
			</div>
		</div>
		<!-- /top navigation -->
		<div class="right_col" role="main">
			<?php if(isset($x_content) && $x_content): ?>
				<div class="row">
					<div class="col-md-12 col-sm-12">
						<div class="x_panel">
							<div class="x_title">
								<h2><?php echo $page_title??''; ?></h2>
								<ul class="nav navbar-right panel_toolbox">
									<li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
									</li>
									<li><a class="close-link"><i class="fa fa-close"></i></a>
									</li>
								</ul>
								<div class="clearfix"></div>
							</div>
							<div class="x_content">
							<?php endif; ?>
							<?php if(!empty($_SESSION['msg'])): ?>
								<p style="color:red;"><?php echo htmlentities($_SESSION['msg']);?></p>
								<?php $_SESSION['msg']=""; ?>
							<?php endif; ?>