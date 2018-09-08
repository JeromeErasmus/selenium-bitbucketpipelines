USE [master]
GO

/**
 *
 * Object:  Database [Cad_Beta]   
 * Script Date: 10/16/2015 17:17:09
 *
 */
 
CREATE DATABASE [Cad_Beta] ON  PRIMARY 
( NAME = N'Cad_Beta', FILENAME = N'c:\Program Files (x86)\Microsoft SQL Server\MSSQL10.DEVELOPMENT\MSSQL\DATA\Cad_Beta.mdf' , SIZE = 3072KB , MAXSIZE = UNLIMITED, FILEGROWTH = 1024KB )
 LOG ON 
( NAME = N'Cad_Beta_log', FILENAME = N'c:\Program Files (x86)\Microsoft SQL Server\MSSQL10.DEVELOPMENT\MSSQL\DATA\Cad_Beta_log.ldf' , SIZE = 1024KB , MAXSIZE = 2048GB , FILEGROWTH = 10%)
GO

ALTER DATABASE [Cad_Beta] SET COMPATIBILITY_LEVEL = 100
GO

IF (1 = FULLTEXTSERVICEPROPERTY('IsFullTextInstalled'))
begin
EXEC [Cad_Beta].[dbo].[sp_fulltext_database] @action = 'enable'
end
GO

ALTER DATABASE [Cad_Beta] SET ANSI_NULL_DEFAULT OFF 
GO

ALTER DATABASE [Cad_Beta] SET ANSI_NULLS OFF 
GO

ALTER DATABASE [Cad_Beta] SET ANSI_PADDING OFF 
GO

ALTER DATABASE [Cad_Beta] SET ANSI_WARNINGS OFF 
GO

ALTER DATABASE [Cad_Beta] SET ARITHABORT OFF 
GO

ALTER DATABASE [Cad_Beta] SET AUTO_CLOSE OFF 
GO

ALTER DATABASE [Cad_Beta] SET AUTO_CREATE_STATISTICS ON 
GO

ALTER DATABASE [Cad_Beta] SET AUTO_SHRINK OFF 
GO

ALTER DATABASE [Cad_Beta] SET AUTO_UPDATE_STATISTICS ON 
GO

ALTER DATABASE [Cad_Beta] SET CURSOR_CLOSE_ON_COMMIT OFF 
GO

ALTER DATABASE [Cad_Beta] SET CURSOR_DEFAULT  GLOBAL 
GO

ALTER DATABASE [Cad_Beta] SET CONCAT_NULL_YIELDS_NULL OFF 
GO

ALTER DATABASE [Cad_Beta] SET NUMERIC_ROUNDABORT OFF 
GO

ALTER DATABASE [Cad_Beta] SET QUOTED_IDENTIFIER OFF 
GO

ALTER DATABASE [Cad_Beta] SET RECURSIVE_TRIGGERS OFF 
GO

ALTER DATABASE [Cad_Beta] SET  DISABLE_BROKER 
GO

ALTER DATABASE [Cad_Beta] SET AUTO_UPDATE_STATISTICS_ASYNC OFF 
GO

ALTER DATABASE [Cad_Beta] SET DATE_CORRELATION_OPTIMIZATION OFF 
GO

ALTER DATABASE [Cad_Beta] SET TRUSTWORTHY OFF 
GO

ALTER DATABASE [Cad_Beta] SET ALLOW_SNAPSHOT_ISOLATION OFF 
GO

ALTER DATABASE [Cad_Beta] SET PARAMETERIZATION SIMPLE 
GO

ALTER DATABASE [Cad_Beta] SET READ_COMMITTED_SNAPSHOT OFF 
GO

ALTER DATABASE [Cad_Beta] SET HONOR_BROKER_PRIORITY OFF 
GO

ALTER DATABASE [Cad_Beta] SET  READ_WRITE 
GO

ALTER DATABASE [Cad_Beta] SET RECOVERY SIMPLE 
GO

ALTER DATABASE [Cad_Beta] SET  MULTI_USER 
GO

ALTER DATABASE [Cad_Beta] SET PAGE_VERIFY CHECKSUM  
GO

ALTER DATABASE [Cad_Beta] SET DB_CHAINING OFF 
GO




USE [CAD_Beta]
GO

/** 
 *
 * Object:  Table [dbo].[stop_credits]    
 * Script Date: 10/16/2015 14:40:31 
 * 
 */

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
SET ANSI_PADDING ON
GO
CREATE TABLE [dbo].[stop_credits](
	[scr_id] [int] IDENTITY(1,1) NOT NULL,
	[scr_number] [int] NOT NULL,
	[scr_reason] [varchar](255) NOT NULL,
	[deleted_at] [datetime] NULL,
 CONSTRAINT [PK_stop_credits] PRIMARY KEY CLUSTERED 
(
	[scr_id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
SET ANSI_PADDING OFF
GO
SET IDENTITY_INSERT [dbo].[stop_credits] ON
INSERT [dbo].[stop_credits] ([scr_id], [scr_number], [scr_reason], [deleted_at]) VALUES (1, 1, N'A/C overdue - Payment required', NULL)
INSERT [dbo].[stop_credits] ([scr_id], [scr_number], [scr_reason], [deleted_at]) VALUES (2, 2, N'C/C or DD Details need to be verified', NULL)
INSERT [dbo].[stop_credits] ([scr_id], [scr_number], [scr_reason], [deleted_at]) VALUES (3, 3, N'Credit to be taken up', NULL)
INSERT [dbo].[stop_credits] ([scr_id], [scr_number], [scr_reason], [deleted_at]) VALUES (4, 4, N'Payment to be processed before CAD Numbers are issued', NULL)
INSERT [dbo].[stop_credits] ([scr_id], [scr_number], [scr_reason], [deleted_at]) VALUES (5, 5, N'Purchase Order validation required', NULL)
INSERT [dbo].[stop_credits] ([scr_id], [scr_number], [scr_reason], [deleted_at]) VALUES (6, 6, N'Other - see Accounts (default)', NULL)
INSERT [dbo].[stop_credits] ([scr_id], [scr_number], [scr_reason], [deleted_at]) VALUES (7, 7, N'Inactive Customer', NULL)
SET IDENTITY_INSERT [dbo].[stop_credits] OFF


/** 
 *
 * Object:  Table [dbo].[agencies]    
 * Script Date: 10/16/2015 14:40:31 
 * 
 */

CREATE TABLE [dbo].[agencies](
	[ag_id] [int] IDENTITY(1,1) NOT NULL,
	[ag_code] [varchar](8) NOT NULL,
	[ag_name] [varchar](72) NULL,
	[ag_address1] [varchar](36) NULL,
	[ag_address2] [varchar](36) NULL,
	[ag_city] [varchar](26) NULL,
	[ag_postcode] [varchar](12) NULL,
	[ag_area_code] [varchar](5) NULL,
	[ag_phone] [varchar](20) NULL,
	[ag_fax] [varchar](20) NULL,
	[ag_unallocated_cash] [float] NULL,
	[ag_credit_status] [varchar](2) NULL,
	[ag_notes] [text] NULL,
	[ag_mail_format] [varchar](1) NULL,
	[ag_mobile] [varchar](25) NULL,
	[ag_corp_affairs_no] [varchar](11) NULL,
	[ag_contact_name] [varchar](26) NULL,
	[ag_purchase_order_required] [varchar](15) NULL,
	[ag_billing_code] [varchar](8) NULL,
	[ag_credit_limit] [float] NULL,
	[ag_account_type] [varchar](3) NULL,
	[ag_scr_id] [int] NULL,
	[ag_account_group] [varchar](6) NULL,
	[ag_is_sync_update] [bit] NULL,
	[ag_modify_date] [datetime] NOT NULL,
	[ag_create_date] [datetime] NOT NULL,
	[ag_is_contact_name_editable] [bit] NULL,
	[ag_is_freelancer] [bit] NULL,
	[ag_business_number] [nvarchar](50) NULL,
	[ag_fax_area_code] [nvarchar](5) NULL,
	[ag_telephone_area_code] [nvarchar](5) NULL,
	[ag_cty_id] [int] NULL,
	[ag_suburb] [nvarchar](50) NULL,
	[ag_sta_id] [int] NULL,
	[ag_allow_late_submission] [bit] NOT NULL,
	[ag_accounts_contact] [varchar](50) NULL,
	[ag_accounts_phone_number] [varchar](15) NULL,
	[ag_accounts_email] [varchar](50) NULL,
	[ag_is_disabled] [bit] NULL,
	[ag_is_approved] [bit] NULL,
	[ag_agr_id] [int] NULL,
	[ag_billing_sub_group] [varchar](255) NULL,
	[ag_abn] [varchar](50) NULL,
	[ag_net_id] [int] NULL,
	[ag_primary_contact_email] [varchar](255) NULL,
	[ag_primary_contact_notification_id] [varchar](50) NULL,
	[ag_add_taxable] [tinyint] NULL,
	[ag_overseas_gst] [varchar](1) NULL,
	[ag_state] [varchar](100) NULL,
	[ag_is_active] [bit] NULL,
	[ag_country] [nchar](512) NULL,
  [ag_secondary_contact_name] [nvarchar](50) NULL,
	[ag_secondary_contact_email] [nvarchar](50) NULL,
	[ag_secondary_contact_notification_id] [int] NULL,
 CONSTRAINT [PK_agencies] PRIMARY KEY CLUSTERED 
(
	[ag_id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
SET ANSI_PADDING OFF
GO
/****** Object:  Default [DF__agencies__ModifyDa__4EDDB18F]    Script Date: 10/16/2015 14:47:03 ******/
ALTER TABLE [dbo].[agencies] ADD  CONSTRAINT [DF__agencies__ModifyDa__4EDDB18F]  DEFAULT (getdate()) FOR [ag_modify_date]
GO
/****** Object:  Default [DF__agencies__CreateDa__4DE98D56]    Script Date: 10/16/2015 14:47:03 ******/
ALTER TABLE [dbo].[agencies] ADD  CONSTRAINT [DF__agencies__CreateDa__4DE98D56]  DEFAULT (getdate()) FOR [ag_create_date]
GO
/****** Object:  Default [DF__agencies__IsContac__0DCF0841]    Script Date: 10/16/2015 14:47:03 ******/
ALTER TABLE [dbo].[agencies] ADD  CONSTRAINT [DF__agencies__IsContac__0DCF0841]  DEFAULT ((0)) FOR [ag_is_contact_name_editable]
GO
/****** Object:  Default [DF__agencies__AllowLat__3C69FB99]    Script Date: 10/16/2015 14:47:03 ******/
ALTER TABLE [dbo].[agencies] ADD  CONSTRAINT [DF__agencies__AllowLat__3C69FB99]  DEFAULT ((0)) FOR [ag_allow_late_submission]
GO
/****** Object:  Default [DF__agencies__isDisabl__3D5E1FD2]    Script Date: 10/16/2015 14:47:03 ******/
ALTER TABLE [dbo].[agencies] ADD  CONSTRAINT [DF__agencies__isDisabl__3D5E1FD2]  DEFAULT ((0)) FOR [ag_is_disabled]
GO
/****** Object:  Default [DF_agencies_ag_is_active]    Script Date: 10/16/2015 14:47:03 ******/
ALTER TABLE [dbo].[agencies] ADD  CONSTRAINT [DF_agencies_ag_is_active]  DEFAULT ((0)) FOR [ag_is_active]
GO
/****** Object:  ForeignKey [FK__agencies__StopCred__5CA1C101]    Script Date: 10/16/2015 14:47:03 ******/
ALTER TABLE [dbo].[agencies]  WITH CHECK ADD  CONSTRAINT [FK__agencies__StopCred__5CA1C101] FOREIGN KEY([ag_scr_id])
REFERENCES [dbo].[stop_credits] ([scr_id])
GO
ALTER TABLE [dbo].[agencies] CHECK CONSTRAINT [FK__agencies__StopCred__5CA1C101]
GO


set identity_insert [Cad_Beta].[dbo].[agencies] off
INSERT INTO [Cad_Beta].[dbo].[agencies]
([ag_code]
,[ag_name]
,[ag_address1]
,[ag_address2]
,[ag_city]
,[ag_postcode]
,[ag_area_code]
,[ag_phone]
,[ag_fax]
,[ag_unallocated_cash]
,[ag_credit_status]
,[ag_notes]
,[ag_mail_format]
,[ag_mobile]
,[ag_corp_affairs_no]
,[ag_contact_name]
,[ag_purchase_order_required]
,[ag_billing_code]
,[ag_credit_limit]
,[ag_account_type]
,[ag_account_group]
,[ag_is_sync_update]
,[ag_modify_date]
,[ag_create_date]
,[ag_is_contact_name_editable]
,[ag_is_freelancer]
,[ag_business_number]
,[ag_fax_area_code]
,[ag_telephone_area_code]
,[ag_cty_id]
,[ag_suburb]
,[ag_sta_id]
,[ag_allow_late_submission]
,[ag_accounts_contact]
,[ag_accounts_phone_number]
,[ag_accounts_email]
,[ag_is_disabled])
SELECT 
[AgencyCode]
,[Name]
,[Address1]
,[Address2]
,[City]
,[Postcode]
,[AreaCode]
,[Phone]
,[Fax]
,[UnallocatedCash]
,[CreditStatus]
,[Notes]
,[MailFormat]
,[Mobile]
,[CorpAffairsNo]
,[ContactName]
,[PurchaseOrderRequired]
,[BillingCode]
,[CreditLimit]
,[AccountType]
,[AccountGroup]
,[IsSyncUpdate]
,[ModifyDate]
,[CreateDate]
,[IsContactNameEditable]
,[IsFreelancer]
,[BusinessNumber]
,[FaxAreaCode]
,[TelephoneAreaCode]
,[CountryId]
,[Suburb]
,[StateId]
,[AllowLateSubmission]
,[AccountsContact]
,[AccountsPhoneNumber]
,[AccountsEmail]
,[isDisabled]
FROM [migration_old].[dbo].[agency]
where AgencyId is not null
set identity_insert [Cad_Beta].[dbo].[agencies] off
INSERT INTO [Cad_Beta].[dbo].[agencies]
([ag_code]
,[ag_name]
,[ag_address1]
,[ag_address2]
,[ag_city]
,[ag_state]
,[ag_postcode]
,[ag_area_code]
,[ag_phone]
,[ag_fax]
,[ag_unallocated_cash]
,[ag_credit_status]
,[ag_notes]
,[ag_mail_format]
,[ag_mobile]
,[ag_corp_affairs_no]
,[ag_contact_name]
,[ag_purchase_order_required]
,[ag_billing_code]
,[ag_credit_limit]
,[ag_account_type]
,[ag_account_group]
,[ag_is_sync_update]
,[ag_modify_date]
,[ag_create_date]
,[ag_is_contact_name_editable]
,[ag_is_freelancer]
,[ag_business_number]
,[ag_fax_area_code]
,[ag_telephone_area_code]
,[ag_cty_id]
,[ag_suburb]
,[ag_sta_id]
,[ag_allow_late_submission]
,[ag_accounts_contact]
,[ag_accounts_phone_number]
,[ag_accounts_email]
,[ag_is_disabled]
)
SELECT 
[AgencyCode]
,[Name]
,[Address1]
,[Address2]
,[City]
,[State]
,[Postcode]
,[AreaCode]
,[Phone]
,[Fax]
,[UnallocatedCash]
,[CreditStatus]
,[Notes]
,[MailFormat]
,[Mobile]
,[CorpAffairsNo]
,[ContactName]
,[PurchaseOrderRequired]
,[BillingCode]
,[CreditLimit]
,[AccountType]
,[AccountGroup]
,[IsSyncUpdate]
,[ModifyDate]
,[CreateDate]
,[IsContactNameEditable]
,[IsFreelancer]
,[BusinessNumber]
,[FaxAreaCode]
,[TelephoneAreaCode]
,[CountryId]
,[Suburb]
,[StateId]
,[AllowLateSubmission]
,[AccountsContact]
,[AccountsPhoneNumber]
,[AccountsEmail]
,[isDisabled]
FROM [migration_old].[dbo].[agency]
where AgencyId is null
--update stop credit reason. Set every record with 't' in freetv to 6(default)
UPDATE [Cad_Beta].[dbo].[agencies] SET [ag_scr_id] = 6 
from [Cad_Beta].[dbo].[agencies] cad join [migration_old].[dbo].[agency]free
on cad.ag_id = free.AgencyId and free.StopCredit = 'T'
update [Cad_Beta].[dbo].[agencies] set ag_purchase_order_required = 'Optional' where ag_purchase_order_required = 'O'
update [Cad_Beta].[dbo].[agencies] set ag_purchase_order_required = 'Mandatory' where ag_purchase_order_required = 'M'

UPDATE [Cad_Beta].[dbo].[agencies] 
SET ag_is_approved = 1;

/**
 *
 * Object:  Table [dbo].[advertisers]    
 * Script Date: 10/16/2015 16:57:06 
 *
 */

CREATE TABLE [dbo].[advertisers](
	[adv_id] [int] IDENTITY(1,1) NOT NULL,
	[adv_old_id] [int] NULL,
	[adv_code] [varchar](6) NOT NULL,
	[adv_name] [varchar](72) NULL,
	[adv_address1] [varchar](30) NULL,
	[adv_address2] [varchar](30) NULL,
	[adv_city] [varchar](20) NULL,
	[adv_state] [varchar](20) NULL,
	[adv_postcode] [varchar](4) NULL,
	[adv_area_code] [varchar](3) NULL,
	[adv_phone] [varchar](12) NULL,
	[adv_fax] [varchar](12) NULL,
	[adv_cad_notes] [text] NULL,
	[adv_mail_format] [varchar](1) NULL,
	[adv_telex] [varchar](12) NULL,
	[adv_corp_affairs_no] [varchar](9) NULL,
	[adv_contact_name] [varchar](20) NULL,
	[adv_default_agency] [varchar](5) NULL,
	[adv_default_advertiser_category] [varchar](2) NULL,
	[adv_is_sync_update] [bit] NULL,
	[adv_modify_date] [datetime] NULL,
	[adv_create_date] [datetime] NULL,
	[adv_is_charity] [bit] NULL,
	[adv_state_id] [int] NULL,
	[adv_allow_late_submission] [bit] NULL,
	[adv_abn] [varchar](255) NULL,
	[adv_is_approved] [bit] NULL,
	[adv_charity_last_checked] [datetime] NULL,
	[adv_acc_notes] [text] NULL,
	[adv_is_disabled] [tinyint] NOT NULL,
	[adv_is_active] [tinyint] NOT NULL,
 CONSTRAINT [PK_advertisers] PRIMARY KEY CLUSTERED 
(
	[adv_id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]

GO

SET ANSI_PADDING OFF
GO

ALTER TABLE [dbo].[advertisers] ADD  CONSTRAINT [DF__advertise__Modif__0BE6BFCF]  DEFAULT (getdate()) FOR [adv_modify_date]
GO

ALTER TABLE [dbo].[advertisers] ADD  CONSTRAINT [DF__advertise__Creat__0AF29B96]  DEFAULT (getdate()) FOR [adv_create_date]
GO

ALTER TABLE [dbo].[advertisers] ADD  CONSTRAINT [DF__advertise__Allow__787EE5A0]  DEFAULT ((0)) FOR [adv_allow_late_submission]
GO

ALTER TABLE [dbo].[advertisers] ADD  CONSTRAINT [DF__advertise__adv_i__6383C8BA]  DEFAULT ((0)) FOR [adv_is_disabled]
GO

ALTER TABLE [dbo].[advertisers] ADD  CONSTRAINT [DF_advertisers_adv_is_active]  DEFAULT ((0)) FOR [adv_is_active]
GO

set identity_insert [Cad_Beta].[dbo].[advertisers] off
INSERT INTO [Cad_Beta].[dbo].[advertisers]
([adv_old_id]
,[adv_code]
,[adv_name]
,[adv_address1]
,[adv_address2]
,[adv_city]
,[adv_state]
,[adv_postcode]
,[adv_area_code]
,[adv_phone]
,[adv_fax]
,[adv_cad_notes]
,[adv_mail_format]
,[adv_telex]
,[adv_corp_affairs_no]
,[adv_contact_name]
,[adv_default_agency]
,[adv_default_advertiser_category]
,[adv_is_sync_update]
,[adv_modify_date]
,[adv_create_date]
,[adv_is_charity]
,[adv_state_id]
,[adv_allow_late_submission]
,[adv_abn])
SELECT [AdvertiserId]
,[AdvertCode]
,[Name]
,[Address1]
,[Address2]
,[City]
,[State]
,[Postcode]
,[AreaCode]
,[Phone]
,[Fax]
,[Notes]
,[MailFormat]
,[Telex]
,[CorpAffairsNo]
,[ContactName]
,[DefaultAgency]
,[DefaultAdvertiserCategory]
,[IsSyncUpdate]
,[ModifyDate]
,[CreateDate]
,[IsCharity]
,[StateId]
,[AllowLateSubmission]
,[ABN]
FROM [migration_old].[dbo].[advertiser]
set identity_insert [Cad_Beta].[dbo].[advertisers] off

UPDATE [Cad_Beta].[dbo].[advertisers]
SET adv_is_approved = 1;

CREATE TABLE [dbo].[active_directory_failures](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[timestamp] [datetime] NOT NULL
) ON [PRIMARY]

GO

GO
EXEC sp_RENAME 'agencies.ag_primary_contact_notification' , 'ag_primary_contact_notification_id', 'COLUMN'

GO

GO

ALTER TABLE dbo.agencies
ADD
[ag_secondary_contact_name] [varchar](255) NULL,
[ag_secondary_contact_email] [varchar](255) NULL,
[ag_secondary_contact_notification_id] [int] NULL

GO

GO
ALTER TABLE dbo.agencies
ALTER COLUMN ag_primary_contact_notification_id int
GO