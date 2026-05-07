# Chapter 3
## METHODOLOGY

This chapter presents the comprehensive methodology employed in the development and design of the Gym Membership Management System. It outlines the systematic approaches used to gather requirements, analyze the existing processes, and design the proposed system architecture. Additionally, it details the materials, tools, and techniques utilized throughout the development lifecycle to ensure the system meets specified objectives while maintaining quality and efficiency standards.

---

## 3.1 Methodology / Model Used

*(Insert methodology/model diagram here — e.g., showing iterative cycles of planning, design, development, and testing)*

The Gym Membership Management System was developed utilizing the **Agile methodology**, specifically following an iterative and incremental approach. The Agile model was selected as the primary software development methodology due to its flexibility and responsiveness to evolving business requirements in a gymnasium environment where membership patterns, payment methods, and operational needs frequently change.

**What the Model Is**

Agile is an iterative software development approach that emphasizes continuous delivery, customer collaboration, and adaptive planning. Rather than delivering the entire system in one phase, Agile divides development into smaller, manageable increments called sprints, each typically lasting one to four weeks. This approach prioritizes working software, customer feedback, and team collaboration over extensive documentation and rigid planning.

**Why It Is Appropriate for the System**

The Agile methodology is particularly suitable for the Gym Membership Management System for several reasons. First, gymnasium operations are dynamic; membership inquiries, payment processing requirements, and attendance patterns may require system adjustments. Second, the integration of third-party payment gateways (PayMongo) demands flexibility in system design to accommodate API changes or payment flow modifications. Third, QR code-based attendance tracking requires user feedback to refine scanning workflows and optimize data capture. Finally, stakeholders (gym owners, staff, and members) benefit from seeing incremental features deployed regularly, allowing early feedback and course correction.

**Brief Explanation of Its Phases**

The development was structured into iterative phases as follows:

1. **Planning and Requirements Gathering**: Initial consultation with stakeholders to understand membership management, attendance tracking, and payment processing needs.

2. **Design Sprint**: Creating system architecture, database schemas, and user interface mockups based on gathered requirements.

3. **Development Increment**: Building functional features, including member registration, QR code generation, attendance logging, and payment integration.

4. **Testing and Validation**: Conducting unit tests, integration tests, and user acceptance testing to ensure functionality and performance.

5. **Deployment and Feedback**: Releasing features to the production environment and collecting feedback for refinement in subsequent iterations.

6. **Iteration and Refinement**: Addressing feedback, optimizing performance, and implementing enhancement requests in consecutive development cycles.

---

## 3.2 Requirement Analysis

The requirement analysis phase involved systematic investigation of the gymnasium's operational needs, staff workflows, and member expectations. Multiple methods were employed to ensure comprehensive and accurate requirement documentation.

**How Requirements Were Gathered**

Requirements were gathered through a combination of qualitative and quantitative techniques:

- **Stakeholder Interviews**: Direct interviews with gym owners, managers, and administrative staff identified critical business processes, pain points in current operations, and desired system features.

- **Process Observation**: Direct observation of daily gym operations, including member check-ins, payment processing, and attendance management, provided insights into existing workflows and inefficiencies.

- **Surveys and Questionnaires**: Anonymous surveys distributed to gym members and staff revealed user expectations regarding ease of use, mobile accessibility, and payment convenience.

- **Documentation Review**: Analysis of existing membership records, payment logs, and attendance sheets clarified data requirements and operational constraints.

- **Expert Consultation**: Input from IT and database specialists ensured technical feasibility and scalability of proposed solutions.

**Functional Requirements**

The system must satisfy the following functional requirements:

1. **Member Management**: The system shall allow administrators to register, update, view, and manage member profiles, including personal information, membership status, and subscription plans. It shall support soft-delete functionality to maintain historical records while archiving inactive members.

2. **Authentication and Access Control**: The system shall provide secure login mechanisms for administrators and staff, with role-based access control to restrict unauthorized access to sensitive data.

3. **Membership Plans**: The system shall enable administrators to create, modify, view, and archive different membership packages with varying durations and pricing structures.

4. **QR Code Generation and Management**: The system shall automatically generate unique QR codes for each member, which can be displayed on digital or physical ID cards for attendance verification purposes.

5. **Attendance Tracking**: The system shall facilitate real-time scanning of QR codes during member check-ins, recording attendance timestamps and maintaining comprehensive attendance logs.

6. **Payment Processing**: The system shall integrate with PayMongo payment gateway to process membership payments securely, supporting multiple payment methods (credit cards, digital wallets, etc.).

7. **Payment Management**: The system shall allow administrators to record, view, and manage payment transactions, including payment history, receipts, and settlement records.

8. **Reporting and Analytics**: The system shall generate attendance reports, membership statistics, payment summaries, and other analytics to support business decision-making.

9. **Notifications**: The system shall notify administrators and members regarding membership renewals, payment confirmations, and system updates through appropriate channels.

10. **Dashboard and Analytics**: The system shall provide administrators with a comprehensive dashboard displaying key metrics, such as active members, attendance trends, and revenue statistics.

**Non-functional Requirements**

The system must satisfy the following non-functional requirements:

1. **Security**: Data shall be stored securely with proper database credential management. For localhost development purposes, HTTP is utilized; however, security best practices for input validation and SQL injection prevention are implemented throughout.

2. **Performance**: The system shall respond to user requests within two seconds under normal operating conditions on localhost. Database queries shall be optimized to handle requests efficiently.

3. **Availability**: The system shall be stable during local testing and development, with restarts for maintenance or updates as needed.

4. **Scalability**: The system architecture shall be designed to accommodate growth in the number of members, transactions, and data volume without significant performance degradation.

5. **Usability**: The user interface shall be intuitive and accessible to users with varying levels of technical expertise, requiring minimal training or technical support.

6. **Reliability**: The system shall automatically back up data at regular intervals to prevent data loss. Error handling mechanisms shall gracefully manage system failures and provide appropriate feedback to users.

7. **Maintainability**: The codebase shall follow established coding standards and design patterns, enabling future developers to understand, modify, and extend functionality with minimal effort.

8. **Compatibility**: The system shall function across various web browsers (Chrome, Firefox, Safari, Edge) and devices (desktops, tablets, mobile phones) to ensure accessibility for diverse users.

---

## 3.3 Current Flow Diagram

*(Insert current system flowchart here — showing manual/existing membership, payment, and attendance processes)*

**Existing Process Flow**

Prior to implementing the automated Gym Membership Management System, the gymnasium operated using manual and semi-automated processes that created operational inefficiencies and data management challenges.

**Current Member Registration Process**: When prospective members visited the gymnasium, administrative staff manually completed registration forms containing personal information, contact details, and membership plan selection. These forms were physically filed in cabinets, creating storage management issues and making member data retrieval time-consuming. Duplicate entries and data inconsistencies frequently occurred due to manual data entry.

**Manual Attendance Tracking**: Member attendance was recorded through paper sign-in sheets or basic spreadsheets maintained by front-desk staff. Staff manually recorded member names, check-in times, and activity types. This process was prone to errors, forgetting to log entries, and providing no real-time attendance insights. Additionally, generating attendance reports required extensive manual compilation, consuming considerable administrative time.

**Payment Processing Workflow**: Membership payments were initially collected through cash or checks. Administrative staff manually entered payment information into spreadsheets, creating audit and reconciliation challenges. Payment records lacked proper organization, making it difficult to track payment status, identify outstanding balances, or generate accurate financial reports.

**Membership Plan Management**: Different membership plans were documented in printed materials or spreadsheets. Any changes to pricing or features required manual updates across multiple documents, increasing the likelihood of inconsistencies and creating confusion among staff and members regarding current offerings.

**ID Card Issuance**: Physical ID cards were manually created using basic card printing, with member information typed or handwritten. Cards lacked security features and contained no machine-readable data, making attendance verification difficult and card duplication possible.

**Data Storage and Reporting**: All operational data (membership information, payments, attendance, etc.) were stored in isolated spreadsheets or paper documents. Generating comprehensive reports required manual data compilation, sorting, and calculation, which was time-consuming and error-prone. Decision-making relied on incomplete or delayed information.

**Communication with Members**: Member notifications regarding membership renewals, payment reminders, and system updates were communicated through phone calls or printed notices, resulting in inefficient communication and low response rates.

---

## 3.4 Requirement Documentation

### 3.4.1 Materials

The development of the Gym Membership Management System required diverse hardware, software, and technology resources to ensure robust, scalable, and maintainable system implementation.

**Hardware Requirements**

- **Local Web Server**: Apache (as provided by XAMPP) serving the application on localhost. Standard development machine specifications are sufficient (quad-core processor, 8GB RAM, SSD storage).

- **Local Database Server**: MySQL/MariaDB (as provided by XAMPP) running on the same machine or local network. Standard development storage capacity is sufficient for member records, transaction logs, and attendance data.

- **Development Workstations**: Personal computers or laptops for development team members, equipped with minimum 8GB RAM and 256GB SSD storage to efficiently run XAMPP, local databases, and testing environments.

- **QR Code Scanners**: Mobile devices or web cameras capable of interpreting QR codes during member check-in, connected to the local network or the same machine for testing purposes.

- **Backup Solutions**: Local external storage or periodic manual backups for database preservation during development and testing.

**Software Tools and Frameworks**

- **Web Server Framework**: PHP 7.4 or higher as the server-side scripting language, chosen for its simplicity, widespread hosting support, and suitability for small-to-medium business applications.

- **Database Management System**: MySQL 5.7 or higher (or MariaDB equivalent) for structured data storage, providing relational database capabilities, transaction support, and data integrity mechanisms.

- **Front-End Technologies**: HTML5, CSS3, and JavaScript (ES6+) for building responsive, interactive user interfaces. jQuery or vanilla JavaScript is employed for dynamic DOM manipulation and user interactions.

- **QR Code Libraries**: 
  - **Bacon QR Code** (for generating QR codes server-side)
  - **html5-qrcode, jsQR, and Zxing** (JavaScript libraries for client-side QR code scanning)

- **Payment Gateway Integration**: PayMongo API integration for secure payment processing, supporting multiple payment methods and providing webhook support for transaction status updates.

- **Development IDE**: Visual Studio Code or PhpStorm for code editing, debugging, and version control integration.

- **Version Control**: Git for source code version management, enabling team collaboration and code history tracking.

- **Testing Tools**: PHPUnit for unit testing, manual testing frameworks for integration and user acceptance testing.

- **Development Dependencies**: Composer for PHP package management, facilitating easy installation and updating of libraries and dependencies.

- **Documentation Tools**: Markdown for creating system documentation, technical specifications, and user guides.

**Additional Technologies**

- **Web Browser**: Modern browsers (Chrome, Firefox, Safari, Edge) for testing and accessing the localhost web-based system.

- **Backup Solutions**: Manual backup procedures or batch scripts for periodic database backups during development.

- **Monitoring Tools**: Server monitoring software to track system performance, uptime, and resource utilization.

---

## 3.5 Product and Process Design

### 3.5.1 Proposed System Flow

The proposed Gym Membership Management System introduces significant improvements to the current manual processes through automation, centralized data management, and real-time operational insights.

**Proposed Member Registration Process**: Administrative staff or members access the web-based registration interface to enter personal and membership information. The system validates input data for completeness and accuracy, stores information in a centralized database, and automatically generates unique QR codes and digital ID cards. Automated confirmation emails are sent to members with login credentials and important information. This process is completed within minutes, eliminating manual filing and reducing data entry errors.

**Automated Attendance Tracking**: Members present their QR code (displayed on mobile device or printed card) at the check-in station. The system scans the QR code using web cameras or dedicated scanners, retrieves member information, and automatically records attendance with precise timestamps. The system provides immediate feedback to the member and staff, confirming successful check-in. Attendance data is stored in real-time, accessible to administrators through the dashboard.

**Streamlined Payment Processing**: Members make payments through the secure PayMongo payment gateway, supporting multiple payment methods. The system securely processes transactions, updates member payment status in the database, and sends automated payment confirmation emails with receipts. Administrators can view payment summaries and track outstanding balances through the dashboard. Payment data is reconciled automatically, reducing administrative overhead.

**Centralized Membership Plan Management**: Administrators create, modify, and manage membership plans through an admin interface. Changes are immediately reflected system-wide, ensuring consistency across all platforms. Members view current plans and select options during registration. The system enforces consistent pricing and features across all member interactions.

**Automatic ID Card Generation**: Upon membership registration, the system automatically generates digital and printable ID cards containing member information and unique QR codes. Cards can be displayed on mobile devices or printed physically. QR codes contain encrypted member identifiers, enabling secure and efficient verification.

**Integrated Data Management and Reporting**: All operational data is stored in a unified database, accessible through real-time dashboards and reporting tools. Administrators generate comprehensive reports (attendance summaries, revenue analytics, member statistics) with a single click. Historical data analysis supports evidence-based decision-making.

**Automated Notifications**: The system sends automated notifications to members regarding membership renewals, payment reminders, and important announcements. Administrators receive notifications for critical system events or exceptions.

**How the System Improves Current Processes**

The proposed system addresses key inefficiencies and limitations of the current manual processes:

1. **Efficiency Improvement**: Automated processes eliminate manual data entry, filing, and compilation tasks. Member registration, payment processing, and report generation are completed in a fraction of the previous time.

2. **Data Accuracy**: Centralized database and automated data validation eliminate duplicate entries and inconsistencies inherent in manual processes.

3. **Real-Time Insights**: Live dashboards and analytics provide administrators with immediate operational visibility, enabling quick decision-making and problem identification.

4. **Enhanced Security**: Encrypted QR codes, HTTPS transmission, and secure payment processing protect member data and financial information.

5. **Improved Member Experience**: Members enjoy convenience through online registration, multiple payment options, and quick check-in processes.

6. **Scalability**: The system easily accommodates growth in membership numbers and operational complexity without proportional increases in administrative effort.

7. **Reduced Errors**: Automated processes and validation rules minimize human errors in data entry and processing.

8. **Audit Trail**: Comprehensive system logs provide complete transaction history for compliance and audit purposes.

---

## 3.6 Project Design

### 3.6.1 Software and System Architecture

The Gym Membership Management System follows a **three-tier architecture** pattern, separating concerns into distinct layers for improved maintainability, scalability, and testability.

**Presentation Layer (User Interface)**

The presentation layer comprises responsive web pages built with HTML5, CSS3, and JavaScript. This layer provides intuitive interfaces for different user roles (administrators, staff, members, visitors). Key components include:

- **Admin Dashboard**: Overview of system metrics, member statistics, recent payments, and attendance trends
- **Member Management Pages**: Interfaces for viewing, adding, editing, and archiving member records
- **Membership Plan Management**: Create, modify, and manage membership packages
- **Attendance Interface**: Real-time QR code scanning and attendance logging
- **Payment Management**: Payment recording, viewing, and settlement processes
- **Reporting Module**: Generate and export various operational reports
- **User Authentication**: Secure login interface with session management

The presentation layer communicates with the business logic layer through HTTP requests, sending user inputs and receiving processed data for display.

**Business Logic Layer (Application Server)**

Built primarily with PHP, the business logic layer contains core application functionality, validating user inputs, processing business rules, and orchestrating database operations. Key components include:

- **Authentication Module**: Verify user credentials, manage sessions, enforce role-based access control
- **Member Service**: Handle member registration, profile management, and member-related operations
- **Membership Plan Service**: Manage membership plan creation, modification, and retrieval
- **Payment Service**: Process payments through PayMongo gateway, record payment information, handle payment status
- **Attendance Service**: Log attendance records, retrieve attendance history, generate attendance analytics
- **QR Code Service**: Generate unique QR codes, manage QR code data, validate scanned codes
- **Notification Service**: Send automated emails and notifications to members and administrators
- **Report Service**: Aggregate data and generate various operational reports

The business logic layer enforces business rules (e.g., payment validation, membership status verification) and prevents direct database access from the presentation layer, ensuring data consistency and security.

**Data Layer (Database)**

The data layer consists of a MySQL database storing all application data with optimized schema design:

- **Members Table**: Member profiles, contact information, membership status
- **Membership_Plans Table**: Plan definitions, pricing, features, duration
- **Payments Table**: Payment records, amounts, payment methods, timestamps
- **Attendance Table**: Attendance logs with member IDs, timestamps, and status
- **Users Table**: Administrative and staff user accounts with role assignments
- **Login_Logs Table**: Track user login activities for security auditing
- **QR_Codes Table**: QR code associations with members, encryption keys

The database implements referential integrity through foreign keys, ensuring data consistency across related tables. Indexing on frequently queried columns optimizes query performance.

**Design Considerations**

- **Modularity**: The system is organized into distinct modules (authentication, members, payments, attendance, reports), enabling independent development and testing.

- **Scalability**: Separation of layers allows horizontal scaling of the application server if needed. Database indexing and query optimization support growth in data volume and concurrent users.

- **Usability**: The user interface is designed following web accessibility standards (WCAG 2.1), ensuring usability for diverse user capabilities. Consistent navigation patterns and clear labeling minimize user confusion.

- **Security**: The system implements multiple security layers: input validation, parameterized SQL queries (prepared statements) to prevent SQL injection, role-based access control, and secure handling of sensitive data using industry best practices.

- **Reliability**: Error handling mechanisms gracefully manage exceptions, providing meaningful error messages without exposing system details. Automated database backups prevent data loss.

- **Maintainability**: Code follows PHP coding standards (PSR standards), uses consistent naming conventions, includes inline documentation, and employs design patterns (Factory, Service, etc.) for clarity.

---

### 3.6.2 Context Diagram

*(Insert context diagram here — showing the Gym Membership Management System as a central process with external entities such as Members, Administrators, PayMongo Payment Gateway, and Email Service)*

**System and External Entity Interactions**

The context diagram represents the Gym Membership Management System as the central processing unit with the following external entities and interactions:

**External Entities**

1. **Members**: End-users of the system who register for memberships, access member profiles, make payments, and check in using QR codes. The system receives member data and requests; provides membership information, QR codes, payment confirmation, and attendance records.

2. **Administrators/Staff**: Internal users who manage the system, including member records, membership plans, payment processing, and attendance verification. They access the admin dashboard, manage user accounts, and generate reports. The system provides them with interfaces to manage system data and receive operational analytics.

3. **PayMongo Payment Gateway**: Third-party service for payment processing integration testing. For localhost development, PayMongo test credentials and sandbox environment are utilized to simulate payment transactions without real fund processing.

4. **Email Service**: External service for sending automated emails to members and administrators. The system generates email content (confirmations, notifications, reminders) and sends requests to the email service.

5. **Web Browser/Client Application**: The medium through which users access the system via localhost. Sends user requests (HTTP) and receives system responses (web pages, API responses).

6. **Database Storage**: Internal persistent data storage containing all system data. The system reads from and writes to the database to retrieve, store, and update information.

---

### 3.6.3 Data Flow Diagram (DFD)

*(Insert Data Flow Diagram here — showing processes, data stores, external entities, and data flows for member registration, attendance tracking, and payment processing)*

**Data Flow Diagram Overview**

The Data Flow Diagram illustrates how data moves through the system across various processes, data stores, and external entities.

**External Entities**

1. **Members**: Initiate registration, make payments, and perform check-ins through QR code scanning.

2. **Administrators**: Access admin functions, manage members, create membership plans, approve payments, and view reports.

3. **PayMongo Gateway**: Receives payment requests and returns transaction statuses.

4. **Email Service**: Receives notification requests and delivers emails.

**Key Processes**

1. **Member Registration (Process P1.0)**: Accepts member information from the registration interface, validates input, stores member data in the database, generates unique QR codes, and returns a confirmation with ID card details.

2. **Attendance Check-In (Process P2.0)**: Receives QR code scan data, retrieves associated member information, validates membership status, records attendance timestamp, and confirms successful check-in.

3. **Payment Processing (Process P3.0)**: Receives payment requests from members, sends payment details to PayMongo, receives payment status confirmation, updates member payment status, and sends confirmation to member via email.

4. **Membership Management (Process P4.0)**: Allows administrators to create, modify, or delete membership plans. Changes are stored in the database and made available to all system components.

5. **Report Generation (Process P5.0)**: Retrieves aggregated data from various data stores (members, attendance, payments) based on report criteria and generates formatted reports for administrator viewing.

**Data Stores**

1. **Members Store (D1)**: Stores member profile information, contact details, and membership status.

2. **Attendance Store (D2)**: Records all member check-ins with timestamps and status information.

3. **Payments Store (D3)**: Records all payment transactions, amounts, methods, and status.

4. **Membership Plans Store (D4)**: Contains definitions of available membership plans and their features.

5. **QR Codes Store (D5)**: Maintains associations between members and QR codes with encryption details.

6. **Users Store (D6)**: Stores administrator and staff user accounts with roles and permissions.

**Data Flows**

- **Member Registration Flow**: Member inputs are validated (P1.0), stored in Members Store (D1), QR code data generated (P1.0), stored in QR Codes Store (D5), and confirmation sent to member.

- **Attendance Flow**: QR code data scanned → P2.0 retrieves member from D1 and QR data from D5 → attendance recorded in D2 → check-in confirmation provided.

- **Payment Flow**: Payment request → P3.0 sends to PayMongo → PayMongo returns status → P3.0 updates D3 → confirmation email sent to member.

- **Membership Plan Flow**: Administrator input → P4.0 updates D4 → updated plans available to all other processes.

- **Report Flow**: Administrator requests report → P5.0 queries D1, D2, D3, D4 → aggregated data formatted → report displayed.

---

### 3.6.4 Entity-Relationship Diagram (ERD)

*(Insert Entity-Relationship Diagram here — showing all database entities, attributes, and relationships including cardinalities)*

**Database Schema and Entity Relationships**

The Entity-Relationship Diagram represents the database structure with all entities, attributes, and relationships governing the Gym Membership Management System.

**Primary Entities**

1. **Members Entity**
   - Attributes: MemberID (Primary Key), FirstName, LastName, Email, PhoneNumber, DateOfBirth, Address, JoinDate, MembershipStatus, SoftDelete
   - Relationships: 
     - One-to-Many with Attendance (one member has many attendance records)
     - One-to-Many with Payments (one member makes many payments)
     - Many-to-One with Membership_Plans (many members subscribe to one membership plan)
     - One-to-One with QR_Codes (one member has one active QR code)

2. **Membership_Plans Entity**
   - Attributes: PlanID (Primary Key), PlanName, Duration (months), Price, Features, CreatedDate, IsActive
   - Relationships:
     - One-to-Many with Members (one plan is subscribed by many members)

3. **Payments Entity**
   - Attributes: PaymentID (Primary Key), MemberID (Foreign Key), Amount, PaymentMethod, TransactionDate, PaymentStatus, PaymongoTransactionID, ReceiptURL
   - Relationships:
     - Many-to-One with Members (many payments belong to one member)
     - One-to-Many with Payment_Logs (one payment may have multiple status updates)

4. **Attendance Entity**
   - Attributes: AttendanceID (Primary Key), MemberID (Foreign Key), CheckInTime, CheckOutTime, AttendanceStatus
   - Relationships:
     - Many-to-One with Members (many check-ins belong to one member)

5. **Users Entity**
   - Attributes: UserID (Primary Key), Username, PasswordHash, Email, Role, IsActive, CreatedDate, LastLoginDate
   - Relationships:
     - One-to-Many with Login_Logs (one user has many login records)

6. **QR_Codes Entity**
   - Attributes: QRCodeID (Primary Key), MemberID (Foreign Key), QRCodeData, EncryptionKey, GeneratedDate, IsActive
   - Relationships:
     - Many-to-One with Members (many QR codes may exist historically for one member, but one active)

7. **Login_Logs Entity**
   - Attributes: LogID (Primary Key), UserID (Foreign Key), LoginTime, LogoutTime, IPAddress, Action
   - Relationships:
     - Many-to-One with Users (many login records belong to one user)

8. **Payment_Logs Entity** (Optional tracking table)
   - Attributes: LogID (Primary Key), PaymentID (Foreign Key), PreviousStatus, NewStatus, UpdatedDate
   - Relationships:
     - Many-to-One with Payments (many status changes per payment)

**Relationship Cardinalities**

- **Members ↔ Membership_Plans**: Many-to-One (multiple members can have the same membership plan)
- **Members ↔ Attendance**: One-to-Many (one member can have multiple attendance records)
- **Members ↔ Payments**: One-to-Many (one member can make multiple payments)
- **Members ↔ QR_Codes**: One-to-One (each member has one active QR code, though historical codes may exist)
- **Users ↔ Login_Logs**: One-to-Many (one user can have multiple login entries)
- **Payments ↔ Payment_Logs**: One-to-Many (one payment can have multiple status updates)

**Key Constraints**

- **Primary Key Constraints**: Each entity has a unique identifier ensuring record uniqueness.
- **Foreign Key Constraints**: Relationships between entities maintain referential integrity.
- **Not Null Constraints**: Critical attributes (member names, payment amounts, attendance timestamps) are required.
- **Unique Constraints**: Email addresses and usernames must be unique to prevent duplicates.
- **Check Constraints**: Payment amounts must be positive; membership duration must be greater than zero.
- **Default Values**: IsActive fields default to True; timestamps default to current date/time.

---

## Conclusion

This chapter has presented a comprehensive methodology for developing the Gym Membership Management System, encompassing the Agile development approach, requirement analysis processes, current-state process documentation, and detailed system design architecture. The three-tier architecture, combined with systematic data management through normalized database design, provides a robust foundation for a scalable, secure, and maintainable system. The proposed system significantly improves upon current manual processes through automation, centralization, and real-time operational insights, directly supporting organizational efficiency and member satisfaction objectives.

---

**End of Chapter 3: Methodology**
