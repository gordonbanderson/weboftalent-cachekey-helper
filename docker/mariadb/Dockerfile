FROM mariadb/server:latest

#RUN apt -y update && apt -y upgrade
RUN apt -y autoremove && apt-get -y clean

ARG MARIADB_ROOT_PASSWORD
ENV MARIADB_ROOT_PASSWORD ${MARIADB_ROOT_PASSWORD}
RUN echo 'MARIADB_ROOT_PASSWORD=....'
RUN echo ${MARIADB_ROOT_PASSWORD}
RUN env
