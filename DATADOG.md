# Installing the Datadog Agent in a Lando Environment and Transitioning to SSH Installation

## Introduction

This document outlines the attempted methods for installing the Datadog Agent via `lando.yml` and a custom `Dockerfile.datadog` in a local development environment, followed by the shift to SSH installation due to the absence of Docker in production.

## Background

Datadog is a monitoring service for cloud-scale applications, providing observability to infrastructure, application performance (APM), and logs. Its powerful APM tools are essential for monitoring, troubleshooting, and optimising application performance.

### Attempt 1: `lando.yml` Configuration

Initially, the installation was attempted through Lando, an open-source development tool. The `lando.yml` was modified to include the Datadog Agent. However, this approach was unsuccessful.

#### Issue Encountered

- Lando encapsulates services within Docker containers, but the Datadog Agent failed to communicate effectively with these services.

### Attempt 2: Custom `Dockerfile.datadog`

The second approach involved creating a separate Dockerfile (`Dockerfile.datadog`) to install the Datadog Agent.

#### Issue Encountered

- Similar to the first attempt, the agent didn't function as expected due to the isolated nature of Docker containers.

### Realisation: No Docker in Production

It was later discovered that Docker is not used in the production environment. This rendered the previous attempts ineffective for real-world application monitoring.

## Transition to SSH Installation

Given that Docker isn't used in production, the most viable solution is to install the Datadog Agent directly on the production servers via SSH.

### Steps for SSH Installation

1. **Access Server**: Connect to your production server via SSH.
2. **Download Agent**: Use the appropriate Datadog installation command for your server's OS.
3. **Configuration**: Configure `datadog.yaml` with your Datadog API key and any specific settings needed for your environment.
4. **Start the Agent**: Enable and start the Datadog Agent service.

### Benefits of Datadog and APM

- **Real-Time Insights**: Provides real-time analytics of your application’s performance.
- **Error Tracking**: Quickly identify and troubleshoot errors.
- **Scalability Monitoring**: Monitor your infrastructure's scalability, optimising resource usage.
- **Customisable Dashboards**: Tailor dashboards to display key metrics.
- **End-to-End Visibility**: Track requests across the full stack to understand dependencies and bottlenecks.
- **Improved User Experience**: Detect issues before they impact users, maintaining high service quality.

## Conclusion

The shift to installing the Datadog Agent via SSH is a strategic move aligned with the production environment’s architecture. This approach will enable comprehensive monitoring and performance tracking in a non-Dockerised environment, harnessing the full potential of Datadog’s APM capabilities.
